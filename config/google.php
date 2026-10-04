<?php

declare(strict_types=1);

use Application\Http\Client\GeminiClient\GeminiClient;

return [
    'project_id' => env('GOOGLE_PROJECT_ID'),
    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'gemini_api_key' => env('GENERATIVE_LANGUAGE_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', GeminiClient::DEFAULT_MODEL),
];
