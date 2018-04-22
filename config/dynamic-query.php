<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dynamic Query loadable models
    |--------------------------------------------------------------------------
    |
    | Here you may specify the loadable models for Dynamic Query utility
    | defining the folders and the exclusions to be inspected
    | REMEMBER: YOU HAVE TO SPECIFY EACH SUBFOLDER AS A model-location
    | The Dynamic Query model will get the models only on the root
    | of the folder specified in path, excluding the files specified in exclusions
    |
    */

    'model-locations' => [
        'root' => [
            'path' => app_path(),
            'exclusions'=>['BaseModel.php'],
            'namespace'=>'App'
        ],
        'content' => [
            'path' => app_path("Content"),
            'exclusions'=>['Content.php', 'ContentStatus.php'],
            'namespace'=>'App\Content'
        ]
    ],

];
