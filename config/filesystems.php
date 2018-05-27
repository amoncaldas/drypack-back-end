<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'root' => [
            'driver' => 'local',
            'root' => base_path(),
        ],
        'deploy' => [
            'driver' => 'local',
            'root' => base_path('deploy'),
        ],
        'package' => [
            'driver' => 'local',
            'root' => base_path('package'),
        ],
        'upload' => [
            'driver' => 'local',
            'root' => storage_path('upload'),
        ],
        'scripts' => [
            'driver' => 'local',
            'root' => base_path('scripts'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
        'ftp' => [
            'driver'    => env('FTP_FILESYSTEM', env('DRIVER','ftp')), // can be ftp or sftp
            'host'      => env('FTP_HOST','ftp.tld'),
            'port'      => env('FTP_PORT', 22),
            'username'  => env('FTP_USER','ftp-user'),
            'password'  => env('FTP_PASSWD','ftp-password'),
            'root'      => env('FTP_FILESYSTEM_ROOT', env('FTP_ROOT','./')),
            'passive'   => env('FTP_PASSIVE',false),
            'ssl'       => env('FTP_SSL',false),
            'timeout'   => env('FTP_TIMEOUT',30),

            // Only necessary if using SFTP driver
            //'privateKey' => env('FTP_PRIVATE_KEY', 'full-path-to-private-key'),
        ]
    ],

];
