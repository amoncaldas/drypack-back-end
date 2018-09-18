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

    // paths
    'upload_path' => storage_path('upload'),
    'thumb_path' => storage_path('upload/thumb'),
    'thumb_temp_path' => storage_path('upload/thumb/temp'), // only used in case that 'storage_policy' = 'indb'

    // extensions allowed to be uploaded by type
    'image_allowed_extensions' => ["jpg", "jpeg", "gif", "png", "tiff"],
    'video_allowed_extensions' => ["mp4"],
    'audio_allowed_extensions' => ["mp3"],
    'document_allowed_extensions' => ["pdf", "docx"],

    // videos are always stored in the file system, even if the configuration is 'indb'
    'storage_policy' => "indb", // can be 'filesystem' or 'indb'

    // auto thumb options
    'auto_thumb_proportional' => true,
    'auto_thumb' => true, // if the two thumb sizes must be generated automatically, when the file is uploaded
    'auto_thumb_width' => 240,
    'auto_thumb_height' => 240,
    'auto_medium_thumb_width' => 1024,
    'auto_medium_thumb_height' => 600,
];
