<?php

namespace App\Http\Controllers\Content\MediaExternalVideoAdapters;


use App\Content\Media;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\Vimeo;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\Youtube;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\IMediaExternalVideo;


class VideoAdapterBuilder
{

    // youtube constants
    protected const YOUTUBE_HOSTS = ["youtube.com","youtu.be", "www.youtube.com"];

    // vimeo constants
    protected const VIMEO_HOSTS = ["vimeo.com","www.vimeo.com", "player.vimeo.com"];

    /**
     * Build a IMediaExternalVideo based on the video url
     *
     * @param String $videoUrl
     * @return IMediaExternalVideo
     */
    public static function build($videoUrl) : IMediaExternalVideo {
        $host = parse_url($videoUrl, PHP_URL_HOST);

        // Only youtube and videmo supported at this moment
        if( in_array($host, self::YOUTUBE_HOSTS)) {
           return new Youtube($videoUrl);
        } elseif (in_array($host, self::VIMEO_HOSTS)) {
            return new Vimeo($videoUrl);
        }
    }
}
