<?php

declare(strict_types=1);

return [
    'project_id' => env('GOOGLE_PROJECT_ID'),
    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'gemini_api_key' => env('GENERATIVE_LANGUAGE_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
];
