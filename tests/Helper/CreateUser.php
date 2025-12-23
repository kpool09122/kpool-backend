<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\UserIdentifier;

class CreateUser
{
    /**
     * @param array{
     *     username?: string,
     *     email?: string,
     *     language?: string,
     *     profile_image?: ?string,
     *     password?: string,
     *     email_verified_at?: ?DateTimeImmutable
     * } $overrides
     */
    public static function create(UserIdentifier $userIdentifier, array $overrides = []): void
    {
        DB::table('users')->insert([
            'id' => (string) $userIdentifier,
            'username' => $overrides['username'] ?? 'test-user',
            'email' => $overrides['email'] ?? 'test@example.com',
            'language' => $overrides['language'] ?? 'ja',
            'profile_image' => $overrides['profile_image'] ?? null,
            'password' => Hash::make($overrides['password'] ?? 'password123'),
            'email_verified_at' => isset($overrides['email_verified_at'])
                ? $overrides['email_verified_at']->format('Y-m-d H:i:s')
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function createServiceRole(
        UserIdentifier $userIdentifier,
        string $service,
        string $role
    ): void {
        DB::table('user_service_roles')->insert([
            'user_id' => (string) $userIdentifier,
            'service' => $service,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function createSocialConnection(
        UserIdentifier $userIdentifier,
        SocialProvider $provider,
        string $providerUserId
    ): void {
        DB::table('user_social_connections')->insert([
            'user_id' => (string) $userIdentifier,
            'provider' => $provider->value,
            'provider_user_id' => $providerUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
