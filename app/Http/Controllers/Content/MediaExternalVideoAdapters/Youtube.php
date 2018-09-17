<?php

namespace App\Http\Controllers\Content\MediaExternalVideoAdapters;


use App\Content\Media;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\IMediaExternalVideo;
use App\Http\Controllers\Content\MediaExternalVideoAdapters\VideoData;


class Youtube implements IMediaExternalVideo
{
    // youtube constants
    protected const YOUTUBE_FROM =  "http://youtube.com";
    protected const YOUTUBE_URL_PATTERN =  "http://youtube.com/embed/<VIDEO_ID>";
    protected const YOUTUBE_VIDEO_INFO_PATTERN = "https://www.googleapis.com/youtube/v3/videos?part=id%2C+snippet&id=<VIDEO_ID>&key=<API_KEY>";
    protected const YOUTUBE_PREVIEW_PATTERN = "http://i3.ytimg.com/vi/<VIDEO_ID>/maxresdefault.jpg";

    public function __construct($videoUrl)
    {
       $this->videoUrl = $videoUrl;
    }

    public function getVideoData() : VideoData {
        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=embed/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $this->videoUrl, $matches);
        $videoId = $matches[0];

        $videoData = new VideoData();

        $videoData->preview_image = str_replace("<VIDEO_ID>", $videoId, self::YOUTUBE_PREVIEW_PATTERN);
        $videoData->external_content_id = $videoId;

        $videoData->url = str_replace("<VIDEO_ID>", $videoId, self::YOUTUBE_URL_PATTERN);
        $videoData->from = self::YOUTUBE_FROM;

        $youtubeApiKey = env("YOUTUBE_DATA_API_KEY");
        $videoInfoUrl = str_replace("<API_KEY>", $youtubeApiKey, str_replace("<VIDEO_ID>", $videoId, self::YOUTUBE_VIDEO_INFO_PATTERN));
        $response = file_get_contents($videoInfoUrl);
        if ($response) {
            $video_attributes = json_decode($response, true);
            if (isset($video_attributes["items"]) && isset($video_attributes["items"][0]) && isset($video_attributes["items"][0]["snippet"])) {
                $video_attributes = $video_attributes["items"][0]["snippet"];
                $videoData->length = isset($video_attributes["duration"]) ? $video_attributes["duration"] : null;
                $videoData->captured_at = isset($video_attributes["publishedAt"]) ? \DryPack::parseDate($video_attributes["publishedAt"]) : null;
                $videoData->tags = isset($video_attributes["tags"]) ? implode(",", $video_attributes["tags"] ) : null ;
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
        $media->author_name = $videoData->author_name;
        $media->width = $videoData->width;
        $media->height = $videoData->height;
        $media->captured_at = $videoData->captured_at;
        $media->url = $videoData->url;
    }
}
