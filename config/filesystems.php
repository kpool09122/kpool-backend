<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'verification-documents' => [
            'driver' => env('VERIFICATION_DOCUMENTS_DRIVER', 'local'),
            'root' => storage_path('app/verification-documents'),
            'throw' => false,
        ],
    ],
];
