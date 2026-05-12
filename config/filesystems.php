<?php

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

        'verification-documents' => [
            'driver' => env('VERIFICATION_DOCUMENTS_DRIVER', 'local'),
            'root' => storage_path('app/verification-documents'),
            'throw' => false,
        ],
    ],
];
