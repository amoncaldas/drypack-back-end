<?php

namespace App\Http\Controllers\Content;

use Lang;
use \App;
use \Input;
use App\Content\Media;
use App\Http\Requests;
use App\Content\MediaText;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Authorization\Authorization;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use \App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\VideoAdapterBuilder;


class MediaController extends CrudController
{

    protected function getModel()
    {
        return Media::class;
    }


    /**
     * Apply query filters basd in the request parameters and the user authenticated
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyFilters(Request $request, $query) {
        // We want to get all the fields, except the 'content',
        // to avoid transfering a huge amount of data while in listing
        // mode. The 'url' attribute of each model have the endpoint that
        // can be used to retrive the media content
        $attrs = Media::getListSelectFields();

        $query = $query->select($attrs);
        $query = $query->with('mediaTexts')->with('author')->with('owner')->with('categories')->orderBy('created_at', 'desc');

        $query = $query->whereHas('mediaTexts', function ($textQuery) use ($request) {
            if ($request->has('title')  && $request->title != null) {
                $textQuery = $textQuery->where('title', 'like', '%'.$request->title.'%');
            }
            if ($request->has('locale') && $request->locale != null) {
                $textQuery = $textQuery->where('locale', $request->locale);
            }
            if ($request->has('tags') && $request->tags != null) {
                $textQuery = $textQuery->where('tags', 'ilike', '%'.$request->tags.'%');
            }
        });

        if ($request->has('type') && $request->type != null ) {
            $query = $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status != null ) {
            $query = $query->where('status', $request->status);
        }

        if ($request->has('author_name') && $request->author_name != null ) {
            $query = $query->where('author_name', 'ilike', '%'.$request->author_name.'%');
        }

        if ($request->has('categories') && $request->categories != null && is_array($request->categories)) {
            $query = $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->whereIn('id', explode(',', $request->categories));
            });
        }

        $this->applyIndexOthersPermissionFilter($query);
        $this->applyAvailabilityTimeFilter($query);
    }

    /**
     * Check the time availability of the media
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param integer $id
     * @return void
     */
    protected function beforeShow(Request $request, $query, $id) {
        $this->applyAvailabilityTimeFilter($query);
    }


    /**
     * Set author and media texts before saving it
     *
     * @param Request $request
     * @param Model $media
     * @return void
     */
    protected function beforeSave(Request $request, Model $media) {
        $this->checkUpdateOwnerPermission($request, "media");

        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions("media");

        if($media->type === Media::HTML_TYPE && isset($resourceActions["save_html_media"]) && !$user->hasResourcePermission("media", "save_html_media")) {
            $msg = Lang::get('business.you_dont_have_permission_to_save', ['type' => Lang::get('validation.types.html')]);
            throw new BusinessException($msg);
        }
        if($media->type === Media::EXTERNAL_VIDEO_TYPE && isset($resourceActions["save_external_video"]) && !$user->hasResourcePermission("media", "save_external_video")) {
            $msg = Lang::get('business.you_dont_have_permission_to_save', ['type' => Lang::get('validation.types.external_video')]);
            throw new BusinessException($msg);
        }

        if ($request->published_at) {
            $request->published_at = DryPack::parseDate($request->published_at);
        }
        if ($request->expired_at) {
            $request->expired_at = DryPack::parseDate($request->expired_at);
        }

        if ($request->has("status")) {
            $media->status = $request->status;
        }

        $this->setAuthorAndOwner($request, $media);

        if ($request->type === Media::EXTERNAL_VIDEO_TYPE) {
            $this->setExternalContentData($request, $media);
        }
        elseif ($request->type === Media::HTML_TYPE) {
            $media->mimetype = "text/html";
            $media->storage_policy = "indb";
            $media->preview_image = null;
            $media->dimension_type = $media->width_unit === "%" &&  $media->height_unit === "%" ? Media::DIMENSION_TYPE_RESPONSIVE : Media::DIMENSION_TYPE_SIZED;
        }
    }

    /**
     * Set the external video Data
     *
     * @param Model $media
     * @return void
     */
    private function setExternalContentData(Request $request, Model $media) {
        if ($media->type === Media::EXTERNAL_VIDEO_TYPE) {
            $videoAdapter = VideoAdapterBuilder::build($media->url);
            $videoAdapter->addVideoData($media);
        }
    }

    /**
     * Checks if the request is not trying to store an only uploadable content type
     *
     * @param Request $request
     * @param Model $task
     * @return void
     */
    public function beforeStore(Request $request, Model $task)
    {
        $onlyCreatableViaUploadTypes = [Media::IMAGE_TYPE, Media::AUDIO_TYPE, Media::DOCUMENT_TYPE, Media::VIDEO_TYPE];
        if (in_array($request->type, $onlyCreatableViaUploadTypes)) {
            $msg = Lang::get('business.only_storable_via_upload', ['type' => $request->type]);
            throw new BusinessException($msg);
        }
    }

    /**
     * Check if the curent user can destroy/delete the media. If not, raise an BusinessException
     *
     * @param Request $request
     * @param Model $media
     * @return void
     */
    protected function beforeDestroy(Request $request, Model $media) {
        $this->checkDestroyOthersPermission($media, "media");
    }

    /**
     * Check if the curent user can update the media. If not, raise an BusinessException
     *
     * @param Request $request
     * @param Model $media
     * @return void
     */
    protected function beforeUpdate(Request $request, Model $media) {
       $this->checkUpdateOthersPermission($media, "media");
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
                $tags = isset($value['tags']) ? $value['tags'] : null;
                $mediaText = new MediaText(['title'=>$value['title'], 'desc'=>$value['desc'], 'locale'=>$locale, 'tags'=>$tags]);
                $media->mediaTexts()->save($mediaText);
            }
        }

        // remove all categories and (re)save them
        if(isset($request->categories)) {
            $media->categories()->detach();
            $ids = array_pluck($request->categories, 'id');
            $media->categories()->attach($ids);
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
                    $media_text["tags"] = isset($value["tags"])? $value["tags"] : null;
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


    /**
     * Return the content validation rules
     *
     * @param Request $request
     * @param Model $obj
     * @return array $validations rules
     */
    protected function getValidationRules(Request $request, Model $media) {
        $isUpdate = false;

        $types = implode(',', Media::getValidTypes());
        $locales = implode(',', array_keys(Config::get('i18n.locales')));
        $statuses = implode(',', [Media::PUBLISHED_STATUS, Media::DRAFT_STATUS]);
        $validations = [
            'type' => 'required|in:'.$types,
            'status' => 'required|in:'.$statuses,
            'author_name'=>'required|min:2',
            'media_texts'=>'required|array|min:1',
            'media_texts.*.title'=>'required|min:3',
            'media_texts.*.locale'=>'required|min:2|in:'.$locales
        ];

        // is update
        if (isset($media->id)) {
            $isUpdate = true;
            $validations["owner_id"] = 'exists:users,id';
        }

        switch ($request->type) {
            case Media::IMAGE_TYPE:
                $validations["file_name"] = "required";
                break;
            case Media::AUDIO_TYPE:
                $validations["file_name"] = "required";
                break;
            case Media::AUDIO_TYPE:
                $validations["file_name"] = "required";
                break;
            case Media::DOCUMENT_TYPE:
                $validations["file_name"] = "required";
                break;
            case Media::EXTERNAL_VIDEO_TYPE:
                if (!$isUpdate) {
                    $validations["url"] = "required";
                }
                break;
            case Media::HTML_TYPE:
                $validations["content"] = "required";
                $validations["width"] = "required|integer";
                $validations["height"] = "required|integer";
                $validations["width_unit"] = "required|in:'px', '%', 'em', 'vw'";
                $validations["height_unit"] = "required|in:'px', '%', 'em', 'vw'";
                break;
        }

        // In case the media status is as 'publised', the publish date is required
        if ($request->status === Media::PUBLISHED_STATUS) {
            $validations["published_at"] = 'required|date';
        }

        // return $validations;
        return $validations;
    }

    /**
     * Specify custom messages for the validation
     *
     * @return void
     */
    protected function messages($request) {
        $messages = [
           "media_texts.required" => Lang::get('validation.media_text_in_all_cultures_required'),
           "media_texts.array" => Lang::get('validation.media_text_in_all_cultures_required'),
           "media_texts.min" => Lang::get('validation.media_text_in_all_cultures_required'),
           "height_unit" =>  Lang::get('validation.only_the_followings_units_are_valid', ['units' => "px, %, em, vw"]),
           "width_unit" =>  Lang::get('validation.only_the_followings_units_are_valid', ['units' => "px, %, em, vw"])
        ];

        $locales = implode(',', array_keys(Config::get('i18n.locales')));
        foreach ($request->media_texts as $key => $value) {
            $messages["media_texts.$key.title.required"] = Lang::get('validation.media_text_in_all_cultures_required');
            $messages["media_texts.$key.locale.required"] = Lang::get('validation.media_text_in_all_cultures_required');
            $messages["media_texts.$key.locale.in"] =  Lang::get('validation.only_the_followings_cultures_are_valid',  ['locales' => $locales]);
        }

        return $messages;
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
        $file = $this->getAndValidateUploadFile($request);

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
     * @param Integer $id
     * @param String $slug - media name name free of special characters and camel cased
     * @param String $sizeName - 'original'|'medium'
     * @return \Illuminate\Http\Response
     */
    public function showContent(Request $request, $id, $slug, $sizeName = null) {
        // if hotlink is disabled (serve image to external servers)
        if (env('DENY_HOTLINK') === true && $this->isExternalRequest()) {
            return response("Access refused.", 403);
        }
        $media = Media::findOrFail($id);
        if ($media){
            if ($this->checkAvailabilityTimeFilter($media) === false) {
                return response("Media not found.", 404);
            }
            if (!$this->isAdmin() && $media->status === Media::DRAFT_STATUS) {
                return response("Media not found.", 404);
            }
            if ($media->slug !== $slug) {
                return redirect($media->getUrl(), 301);
            }
            // videos are always stored in the file system, even if the configuration is 'indb'
            // because they can be very big
            if($media->storage_policy === Media::STORAGE_POLICY_FILESYSTEM || $media->type === Media::VIDEO_TYPE) {
                $fileContents = File::get($media->resolveFileLocation());
            }
            else { // storage_policy === "indb", so the data is stored in the content/thumb_medium attribute
                if (!isset($sizeName) || $sizeName === Media::ORIGINAL_SIZE_NAME) {
                    $fileContents = base64_decode($media->content);
                } elseif ($sizeName === Media::MEDIUM_SIZE_NAME) {
                    $fileContents = base64_decode($media->thumb_medium);
                }
            }

            $mediaType = $media->type === Media::DOCUMENT_TYPE ? "application" : $media->type;
            return response($fileContents, 200)->header('Content-Type', $mediaType."/".$media->ext);
        } else {
            return response("Media not found.", 404);
        }
    }

    /**
     * Get the data of an external video based in itsurl
     *
     * @param Request $request
     * @param String $videoUrl
     * @return App\Http\Controllers\Content\MediaExternalVideoAdapters\VideoData
     */
    public function getExternalVideoData(Request $request) {
        // $videoUrl = base64_decode($videoUrl);
        $videoUrl = $request->url;
        $videoAdapter = VideoAdapterBuilder::build($videoUrl);
        $parsed = (array) $videoAdapter->getVideoData();
        return [$parsed];
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
        // uploaded media has as default the 'draft' status
        // when the media is saved attached/linked to a content
        // the status of each media wil be auto updated to 'published'
        // this is done to avoid auto listing medias that are related to
        // non public contents
        $media->status = Media::DRAFT_STATUS;
        $media->file_name = $file->getClientOriginalName();
        $media->mimetype = $file->getMimeType();
        $media->ext = $file->getClientOriginalExtension();

        $media->type = \explode("/", $media->mimetype)[0];
        if (in_array($media->ext, Config::get('media-uploader.document_allowed_extensions'))) {
            $media->type = Media::DOCUMENT_TYPE;
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
    protected function getAndValidateUploadFile(Request $request) {
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
