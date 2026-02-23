<?php

$awsCaBundle = env('AWS_CA_BUNDLE');
$awsVerifySsl = $awsCaBundle ?: filter_var(env('AWS_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN);
$awsUsePathStyle = filter_var(env('AWS_USE_PATH_STYLE_ENDPOINT', false), FILTER_VALIDATE_BOOLEAN);

// Force HTTPS for endpoint so pre-signed URLs use https:// (avoids mixed content on HTTPS pages).
$awsEndpoint = env('AWS_ENDPOINT');
if ($awsEndpoint !== null && $awsEndpoint !== '') {
    $awsEndpoint = preg_replace('#^http://#i', 'https://', trim($awsEndpoint));
}

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

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => $awsEndpoint,
            'use_path_style_endpoint' => $awsUsePathStyle,
            'http' => [
                'verify' => $awsVerifySsl,
                // Timeouts: when S3 is up, allow enough time for uploads. When S3 is down, lower in .env to fail fast (e.g. 8/12) to avoid gateway 504.
                'connect_timeout' => (int) env('AWS_CONNECT_TIMEOUT', 20),
                'timeout' => (int) env('AWS_TIMEOUT', 45),
            ],
            'throw' => false,
        ],

        // Same as s3 but throws on failure (for sync command so we see the real error).
        's3_throw' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => $awsEndpoint,
            'use_path_style_endpoint' => $awsUsePathStyle,
            'http' => [
                'verify' => $awsVerifySsl,
                'connect_timeout' => (int) env('AWS_CONNECT_TIMEOUT', 20),
                'timeout' => (int) env('AWS_TIMEOUT', 45),
            ],
            'throw' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
