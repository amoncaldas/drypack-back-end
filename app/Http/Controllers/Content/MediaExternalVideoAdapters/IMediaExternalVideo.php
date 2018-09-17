<?php

namespace App\Http\Controllers\Content\MediaExternalVideoAdapters;


use App\Content\Media;


interface IMediaExternalVideo
{
    public function getVideoData() : VideoData;

    public function addVideoData(Media $media);
}
