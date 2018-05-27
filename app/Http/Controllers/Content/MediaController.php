<?php

namespace App\Http\Controllers\Content;

use Lang;
use \Input;
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

    }


    protected function beforeSearch(Request $request, $dataQuery, $countQuery)
    {

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
        $media = $this->getMedia($request, $file);

        // store the media according the storage policy defined in config
        $this->storeMediaContent($media, $file);

        // save the media in database
        $media->save();
        return $media;
    }

    /**
     * Get the Media instance populated from the request
     *
     * @param Request $request
     * @param File $file
     * @return Media $media
     */
    protected function getMedia(Request $request, $file) {
        $media = new Media();
        $media->status = "uploaded";
        $media->file_name = $file->getClientOriginalName();
        $media->mimetype = $file->getMimeType();
        $media->type = \explode("/", $media->mimetype)[0];
        $media->ext = $file->getClientOriginalExtension();

        $user = $this->getUser();

        $media->owner_id = $user->id;

        if($request->has("author_name")) {
            $media->author_name = $request->author_name;
        } else {
            $media->author_name = $user->name;
        }

        if($request->has("author_id")) {
            $media->author_id = $request->author_id;
        } else {
            $media->author_id = $user->id;
        }

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
        if (Config::get('media-uploader.storage_policy') === "filesystem") {
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
        if (!$request->hasFile('media_upload')) {
            throw new BusinessException("no_file_provided");
        }

        if (!$request->file('media_upload')->isValid()) {
            throw new BusinessException("invalid_upload");
        }

        $file = Input::file('media_upload');
        $exts = Config::get('media-uploader.allowed_extensions');
        $extension = $file->getClientOriginalExtension();
        if(!in_array($extension, $exts))
        {
            $msg = Lang::get('business.extension_not_allowed', ['ext' => $extension]);
            throw new BusinessException($msg);
        }
        return $file;
    }
}
