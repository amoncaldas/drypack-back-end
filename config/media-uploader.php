<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media uploader
    |--------------------------------------------------------------------------
    |
    | Here you may specify configurations for the media uploader
    |
    */

    'upload_path' => storage_path('upload'),
    'thumb_path' => storage_path('upload/thumb'),
    'thumb_temp_path' => storage_path('upload/thumb/temp'),
    'image_allowed_extensions' => ["jpg", "jpeg", "gif", "png", "tiff"],
    'video_allowed_extensions' => ["mp4"],
    'audio_allowed_extensions' => ["mp3"],
    'document_allowed_extensions' => ["pdf", "docx"],
    'storage_policy' => "indb", // can be 'filesystem' or 'indb'
    'auto_thumb_proportional' => true,
    'auto_thumb' => true,
    'auto_thumb_width' => 240,
    'auto_thumb_height' => 240,
];
