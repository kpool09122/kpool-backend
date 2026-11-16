<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\AuthorizationConsent;
use Application\Models\Identity\McpAccessToken;
use Application\Models\Identity\McpAuthCode;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Source\Identity\Application\Service\AuthorizationTokenLifecycleServiceInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationTokenLifecycleService implements AuthorizationTokenLifecycleServiceInterface
{
    public function __construct(private InternalApiTokenServiceInterface $internalApiTokenService)
    {
    }

    public function revoke(AuthorizationConsentIdentifier $consentIdentifier): void
    {
        $identifiers = McpAccessToken::query()->where('authorization_consent_id', (string) $consentIdentifier)
            ->get(['id'])->map(static fn (McpAccessToken $token): string => $token->id)->all();
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $identifiers)->update(['revoked' => true]);
        McpAccessToken::query()->where('authorization_consent_id', (string) $consentIdentifier)->update(['revoked' => true]);
        McpAuthCode::query()->where('authorization_consent_id', (string) $consentIdentifier)->update(['revoked' => true]);
        DB::afterCommit(function () use ($identifiers): void {
            foreach ($identifiers as $identifier) {
                $this->internalApiTokenService->forgetCachedAuthorization($identifier);
            }
        });
    }

    public function deleteForIdentity(IdentityIdentifier $identityIdentifier): void
    {
        $consents = AuthorizationConsent::query()->where('identity_id', (string) $identityIdentifier)->select('id');
        $tokens = McpAccessToken::query()->whereIn('authorization_consent_id', $consents)->select('id');
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokens)->delete();
        McpAccessToken::query()->whereIn('authorization_consent_id', $consents)->delete();
        McpAuthCode::query()->whereIn('authorization_consent_id', $consents)->delete();
        AuthorizationConsent::query()->where('identity_id', (string) $identityIdentifier)->delete();
    }
}
