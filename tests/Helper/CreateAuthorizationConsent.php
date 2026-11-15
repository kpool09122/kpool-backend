<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Assert;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CreateAuthorizationConsent
{
    public const string RESOURCE = 'https://mcp.example.test/mcp';
    public const string REDIRECT = 'https://chatgpt.example.test/callback';

    public static function create(): AuthorizationConsent
    {
        config()->set('mcp_oauth', [
            'issuer' => 'https://auth.example.test', 'resource' => self::RESOURCE,
            'access_token_seconds' => 3600, 'refresh_token_seconds' => 2592000, 'authorization_code_seconds' => 300,
        ]);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        Assert::assertNotFalse($key);
        openssl_pkey_export($key, $private);
        $details = openssl_pkey_get_details($key);
        Assert::assertNotFalse($details);
        config()->set('passport.private_key', $private);
        config()->set('passport.public_key', $details['key']);
        $identity = new IdentityIdentifier((string) Str::uuid7());
        CreateIdentity::create($identity, ['email' => Str::uuid7() . '@example.test']);
        $client = app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('ChatGPT', [self::REDIRECT], false);
        $account = new AccountIdentifier((string) Str::uuid7());
        $consent = app()->make(AuthorizationConsentFactoryInterface::class)->create(
            $identity,
            new OAuthClientIdentifier($client->id),
            [AuthorizationScope::WikiRead, AuthorizationScope::DraftRead],
            [new AuthorizationTarget($account)],
            $account,
            new AccountPrincipalIdentifier((string) Str::uuid7()),
            $account,
            self::RESOURCE,
            new DateTimeImmutable()
        );
        app()->make(AuthorizationConsentRepositoryInterface::class)->save($consent);

        return $consent;
    }
}
