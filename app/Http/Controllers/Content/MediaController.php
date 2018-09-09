<?php

namespace App\Http\Controllers\Content;

use Lang;
use \Input;
use \App;
use App\Content\Media;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use \App\Exceptions\BusinessException;
use App\Console\Commands\LinuxCommands;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\CrudController;
use App\Authorization\Authorization;
use App\Content\MediaText;


class MediaController extends CrudController
{

    public function __construct()
    {
        $this->command = new LinuxCommands();
    }

    protected function getModel()
    {
        return Media::class;
    }


    protected function applyFilters(Request $request, $query)
    {
        // We want to get all the fields, except the 'content',
        // to avoid transfering a huge amount of data while in listing
        // mode. The 'url' attribute of each model have the endpoint that
        // can be used to retrive the media content
        $attrs = $query->getModel()->getAllAttributes();
        $content_key = array_search("content",$attrs);
        unset($attrs[$content_key]);
        $attrs[] = "id";
        $query = $query->select($attrs);
        $query = $query->with('mediaTexts')->with('author')->with('owner')->orderBy('created_at', 'desc');

        $query = $query->whereHas('mediaTexts', function ($textQuery) use ($request) {
            if ($request->has('title')) {
                $textQuery = $textQuery->where('title', 'like', '%'.$request->title.'%');
            }
            if ($request->has('locale')) {
                $textQuery = $textQuery->where('locale', $request->locale);
            }
        });

        if ($request->has('type')) {
            $query = $query->where('type', $request->type);
        }

        if ($request->has('author_name')) {
            $query = $query->where('author_name', 'ilike', '%'.$request->author_name.'%');
        }
    }


    protected function beforeSave(Request $request, Model $media)
    {
        $this->setAuthorAndOwner($request, $media);

        if ($request->type === "external_video") {
            $media->status = "saved";
            $media->mimetype = "video/youtube";
            $media->storage_policy = "indb";
            $media->dimension_type = "responsive";
        }
        elseif ($request->type === "html") {
            $media->status = "saved";
            $media->mimetype = "text/html";
            $media->storage_policy = "indb";
            $media->preview_image = "";
            $media->dimension_type = "responsive"; // sized?

            $media->width = "responsive";
            $media->height = "responsive";
            $media->width_unit = "responsive";
            $media->height_unit = "responsive";
        }
    }

    /**
     * Before saving set the media_texts in the main object to be saved internally by the Media class
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function afterSave(Request $request, Model $media) {
        $media_texts = $this->getMediaTexts($request, $media);

        if (isset($media_texts) && is_array($media_texts)) {
            $media->mediaTexts()->delete();

            foreach ($media_texts as $key => $value) {
                $locale = isset($value['locale']) ? $value['locale'] : $key;
                $mediaText = new MediaText(['title'=>$value['title'], 'desc'=>$value['desc'], 'locale'=>$locale]);
                $media->mediaTexts()->save($mediaText);
            }
        }
    }

    /**
     * Get request media texts
     *
     * @param Request $request
     * @return array
     */
    private function getMediaTexts(Request $request, $media) {
        $media_texts = [];

        if($request->has("media_texts")) {
            $texts = $request->media_texts;
            foreach ($texts as $key => $value) {
                $locale = isset($value['locale']) ? $value['locale'] : $key;
                $validLocales = Config::get('i18n.locales');

                if (isset($validLocales[$locale])) {
                    $media_text = [
                        "title"=>$value["title"],
                        "locale"=>$value["locale"]
                    ];
                    $media_text["desc"] = isset($value["desc"])? $value["desc"] : $value["title"];
                    $media_texts[] = $media_text;
                }
            }
        } else {
            $locales = Config::get('i18n.locales');

            foreach ($locales as $locale) {
                $title = $media->file_name;
                if($request->has("title")) {
                    $title = $request->title;
                }
                $desc = $media->file_name;
                if($request->has("desc")) {
                    $desc = $request->desc;
                }

                $media_texts[] = [
                    "title"=>$title,
                    "desc"=>$desc,
                    "locale"=>$locale["id"]
                ];
            }
        }
        return $media_texts;
    }


    protected function getValidationRules(Request $request, Model $obj)
    {
        return [];
    }

    /**
     * Process a file upload submitted
     *
     * @param Request $request
     * @return array - with created media id
     */
    public function upload(Request $request) {

        // We need a lot of memory to handle large files
        ini_set('memory_limit', '512M');

        // Get the file. If invalid, it will throw a BusinessException
        $file = $this->getFileAndValidate($request);

        // transform the data from the request into a populated Media instance
        $media = $this->buildMediaObj($request, $file);

        // check if the loggeduser can upload the type of content (video/audio/image/document) that is being sent
        $this->checkUploadTypePermission($media);

        // as the 'upload' method is part of the CrudController methods (thas already is wrapped in a transaction),
        // we must run the actions in a transaction to make sure that the operation is atomic.
        try {
            \DB::transaction(function () use ($request, $media, $file) {
                // store the media according the storage policy defined in config
                $this->storeMediaContent($media, $file);

                // save the media in database
                $this->beforeSave($request, $media);
                $media->save();
                $this->afterSave($request, $media);
            });
        } catch (Exception $e) {
            return Response::json(['error' => 'messages.duplicatedResourceError'], HttpResponse::HTTP_CONFLICT);
        }

        return $media;
    }

    /**
     * Process a file upload submitted
     *
     * @param Request $request
     * @return array - with created media id
     */
    public function showContent(Request $request, $id) {
        $media = Media::find($id);
        if ($media){
            if($media->storage_policy === "filesystem" || $media->type === Media::VIDEO_TYPE) {
                $fileContents = File::get($media->resolveFileLocation());

            } else { // storage_policy === "indb", so the data is stored in the content attribute
                $fileContents = base64_decode($media->content);
            }

            $mediaType = $media->type === "document" ? "application" : $media->type;
            return response($fileContents, 200)->header('Content-Type', $mediaType."/".$media->ext);
        } else {
            return response("Media not found.", 404);
        }
    }

    /**
     * Check if the loggeduser can upload the type of content (video/audio/image/document) that is being sent
     *
     * @return void
     * @throws BusinessException if not allowed
     */
    protected function checkUploadTypePermission ($media) {
        $permission = "upload_$media->type";
        $mediaType = $media->type;

        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions('media');

        if(!$user->hasResourcePermission("media", $permission)) {
            $msg = Lang::get('business.you_dont_have_permission_to_upload_this_kind_of_content', ['type' => $mediaType]);
            throw new BusinessException($msg);
        }
    }

    /**
     * Set media author and owner
     *
     * @param Request $request
     * @param Media $media
     * @return void
     */
    private function setAuthorAndOwner($request, $media) {
        $user = $this->getUser();

        $media->owner_id = $user->id;

        if($request->has("author_name")) {
            $media->author_name = $request->author_name;
        } else {
            $media->author_name = $user->name;
            $media->author_id = $user->id;
        }

        if($request->has("author_id")) {
            $media->author_id = $request->author_id;
        } else {
            $media->author_id = $user->id;
        }
    }

    /**
     * Get the Media instance populated from the request
     *
     * @param Request $request
     * @param File $file
     * @return Media $media
     */
    protected function buildMediaObj(Request $request, $file) {
        $media = new Media();
        $media->status = "uploaded";
        $media->file_name = $file->getClientOriginalName();
        $media->mimetype = $file->getMimeType();
        $media->ext = $file->getClientOriginalExtension();

        $media->type = \explode("/", $media->mimetype)[0];
        if (in_array($media->ext, Config::get('media-uploader.document_allowed_extensions'))) {
            $media->type = "document";
        }

        $this->setAuthorAndOwner($request, $media);

        return $media;
    }

    /**
     * Sotore media content according the storage policy
     *
     * @param Media $media
     * @param UploadedFile $file
     * @return void
     */
    protected function storeMediaContent(Media $media, UploadedFile $file) {
        if ($media->type === Media::VIDEO_TYPE || Config::get('media-uploader.storage_policy') === "filesystem") {
            $media->unique_name = $media->saveInFileSystem($file);
            $media->storage_policy = Media::STORAGE_POLICY_FILESYSTEM;

        } else {
            $media->content = $media->getTempUploadBase64($file);
            $media->storage_policy = Media::STORAGE_POLICY_INDB;
        }
    }

    /**
     * Get file and validate it. If invalid, throw an exception
     *
     * @param Request $request
     * @return File
     * @throws BusinessException
     */
    protected function getFileAndValidate(Request $request) {
        $file = Input::file('media_upload');
        if (!$file) {
            throw new BusinessException("messages.no_file_provided", $request->file_key);
        }

        if (!$file->isValid()) {
            throw new BusinessException("messages.invalid_upload", $request->file_key);
        }

        $image_exts = Config::get('media-uploader.image_allowed_extensions');
        $video_exts = Config::get('media-uploader.video_allowed_extensions');
        $audio_exts = Config::get('media-uploader.audio_allowed_extensions');
        $document_exts = Config::get('media-uploader.document_allowed_extensions');

        $allowed_exts = array_merge($image_exts, $video_exts, $audio_exts, $document_exts);

        $extension = $file->getClientOriginalExtension();
        if(!in_array(strtolower($extension), $allowed_exts))
        {
            $msg = Lang::get('business.extension_not_allowed', ['ext' => $extension]);
            throw new BusinessException($msg, $request->file_key);
        }
        return $file;
    }
}
