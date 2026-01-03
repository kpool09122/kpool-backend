<?php

declare(strict_types=1);

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_endpoint' => 'https://oauth2.googleapis.com/token',
        'userinfo_endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scopes' => ['openid', 'email', 'profile'],
    ],

    'line' => [
        'client_id' => env('LINE_CLIENT_ID'),
        'client_secret' => env('LINE_CLIENT_SECRET'),
        'redirect_uri' => env('LINE_REDIRECT_URI'),
        'authorization_endpoint' => 'https://access.line.me/oauth2/v2.1/authorize',
        'token_endpoint' => 'https://api.line.me/oauth2/v2.1/token',
        'userinfo_endpoint' => 'https://api.line.me/v2/profile',
        'scopes' => ['profile', 'openid', 'email'],
    ],

    'kakao' => [
        'client_id' => env('KAKAO_CLIENT_ID'),
        'client_secret' => env('KAKAO_CLIENT_SECRET'),
        'redirect_uri' => env('KAKAO_REDIRECT_URI'),
        'authorization_endpoint' => 'https://kauth.kakao.com/oauth/authorize',
        'token_endpoint' => 'https://kauth.kakao.com/oauth/token',
        'userinfo_endpoint' => 'https://kapi.kakao.com/v2/user/me',
        'scopes' => ['profile_nickname', 'profile_image', 'account_email'],
    ],
];
