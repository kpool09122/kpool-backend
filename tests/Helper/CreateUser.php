<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Source\Shared\Domain\ValueObject\UserIdentifier;

class CreateUser
{
    public static function create(UserIdentifier $userIdentifier): void
    {
        DB::table('users')->insert([
            'id' => (string) $userIdentifier,
            'username' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
