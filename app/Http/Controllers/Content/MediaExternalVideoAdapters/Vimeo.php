<?php

namespace App\Http\Controllers\Content\MediaExternalVideoAdapters;


use App\Content\Media;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\IMediaExternalVideo;


class Vimeo implements IMediaExternalVideo
{
    // vimeo constants
    protected const VIMEO_FROM =  "http://vimeo.com";
    protected const VIMEO_URL_PATTERN =  "https://player.vimeo.com/video/<VIDEO_ID>";
    protected const VIMEO_VIDEO_INFO_PATTERN = "http://vimeo.com/api/v2/video/<VIDEO_ID>.json";

    protected $videoUrl;

    public function __construct($videoUrl)
    {
       $this->videoUrl = $videoUrl;
    }

    public function getVideoData() : VideoData {
        preg_match("/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i", $this->videoUrl, $matches);
        $videoId = $matches[1];

        $videoData = new VideoData();

        $videoData->external_content_id = $videoId;
        $videoData->url = str_replace("<VIDEO_ID>", $videoId, self::VIMEO_URL_PATTERN);
        $videoData->from = self::VIMEO_FROM;

        $videoInfoUrl = str_replace("<VIDEO_ID>", $videoId, self::VIMEO_VIDEO_INFO_PATTERN);
        $response = file_get_contents($videoInfoUrl);
        if ($response) {
            $video_attributes = json_decode($response, true);
            if (isset($video_attributes[0])) {
                $video_attributes = $video_attributes[0];
                $videoData->preview_image = isset($video_attributes["thumbnail_large"]) ? $video_attributes["thumbnail_large"] : null;
                $videoData->length = isset($video_attributes["duration"]) ? $video_attributes["duration"] : null;
                $videoData->author_name = isset($video_attributes["user_name"]) ? $video_attributes["user_name"] : null;

                if (isset($video_attributes["width"])) {
                    $videoData->width = $video_attributes["width"];
                    $videoData->width_unit = "px";
                }

                if (isset($video_attributes["height"])) {
                    $videoData->height = $video_attributes["height"];
                    $videoData->height_unit = "px";
                }

                $videoData->tags = isset($video_attributes["tags"]) ? $video_attributes["tags"] : null ;

                $videoData->title = isset($video_attributes["title"]) ? $video_attributes["title"] : null ;
                $videoData->description = isset($video_attributes["description"]) ? $video_attributes["description"] : null ;
            }
        }
        return $videoData;
    }

    public function addVideoData(Media $media) {
        $videoData = self::getVideoData($this->videoUrl);

        $media->preview_image = $videoData->preview_image;
        $media->external_content_id = $videoData->external_content_id;
        $media->status = "saved";
        $media->mimetype = "video/youtube";
        $media->storage_policy = "indb";
        $media->dimension_type = "responsive";
        $media->from = $videoData->from;

        $media->length = $videoData->length;
        $media->author_name = isset($media->author_name) ? $media->author_name : $videoData->author_name;
        $media->width = $videoData->width;
        $media->height = $videoData->height;
        $media->captured_at = isset($media->captured_at) ? $media->captured_at : $videoData->captured_at;
        $media->url = $videoData->url;

        $media->width_unit = $videoData->width_unit;
        $media->height_unit = $videoData->height_unit;
    }

}
