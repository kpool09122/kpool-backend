<?php

$documentDriver = env('VERIFICATION_DOCUMENTS_DRIVER', 'local');

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'image_disk' => env('IMAGE_STORAGE_DISK', 'public'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => public_path('storage'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // The bucket stays private; CloudFront OAC is the only public delivery path.
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'),
            'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
            'bucket' => env('AWS_PUBLIC_IMAGES_BUCKET'),
            'url' => env('IMAGE_BASE_URL'),
            'visibility' => 'private',
            // BucketOwnerEnforced accepts only no ACL or bucket-owner-full-control.
            'options' => ['ACL' => 'bucket-owner-full-control'],
            'throw' => true,
        ],

        'verification-documents' => $documentDriver === 's3' ? [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'),
            'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
            'bucket' => env('AWS_PRIVATE_FILES_BUCKET'),
            'root' => 'verification-documents',
            'visibility' => 'private',
            // BucketOwnerEnforced accepts only no ACL or bucket-owner-full-control.
            'options' => ['ACL' => 'bucket-owner-full-control'],
            'throw' => true,
        ] : [
            'driver' => $documentDriver,
            'root' => storage_path('app/verification-documents'),
            'throw' => false,
        ],
    ],
];
