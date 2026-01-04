<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreateIdentity
{
    /**
     * @param array{
     *     username?: string,
     *     email?: string,
     *     language?: string,
     *     profile_image?: ?string,
     *     password?: string,
     *     email_verified_at?: ?DateTimeImmutable,
     *     delegation_identifier?: ?string,
     *     original_identity_identifier?: ?string
     * } $overrides
     */
    public static function create(IdentityIdentifier $identityIdentifier, array $overrides = []): void
    {
        DB::table('identities')->insert([
            'id' => (string) $identityIdentifier,
            'username' => $overrides['username'] ?? 'test-identity',
            'email' => $overrides['email'] ?? 'test@example.com',
            'language' => $overrides['language'] ?? 'ja',
            'profile_image' => $overrides['profile_image'] ?? null,
            'password' => Hash::make($overrides['password'] ?? 'password123'),
            'email_verified_at' => isset($overrides['email_verified_at'])
                ? $overrides['email_verified_at']->format('Y-m-d H:i:s')
                : null,
            'delegation_identifier' => $overrides['delegation_identifier'] ?? null,
            'original_identity_identifier' => $overrides['original_identity_identifier'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function createSocialConnection(
        IdentityIdentifier $identityIdentifier,
        SocialProvider $provider,
        string $providerUserId
    ): void {
        DB::table('identity_social_connections')->insert([
            'id' => StrTestHelper::generateUuid(),
            'identity_id' => (string) $identityIdentifier,
            'provider' => $provider->value,
            'provider_user_id' => $providerUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
