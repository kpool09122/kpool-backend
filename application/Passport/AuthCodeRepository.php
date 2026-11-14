<?php

declare(strict_types=1);

namespace Application\Passport;

use Application\Models\Identity\McpAuthCode;
use Laravel\Passport\Bridge\AuthCodeRepository as PassportAuthCodeRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;

class AuthCodeRepository extends PassportAuthCodeRepository
{
    public function __construct(private readonly IssuanceContext $issuanceContext)
    {
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $consent = $this->issuanceContext->requireConsent(
            $authCodeEntity->getUserIdentifier(),
            $authCodeEntity->getClient()->getIdentifier(),
            array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $authCodeEntity->getScopes()),
        );
        Passport::authCode()->forceFill([
            'id' => $authCodeEntity->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => json_encode($authCodeEntity->getScopes(), JSON_THROW_ON_ERROR),
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
            'authorization_consent_id' => (string) $consent->identifier(),
        ])->save();
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $code = McpAuthCode::query()->find($codeId);

        return $code === null || $code->revoked || $code->expires_at === null || $code->expires_at->isPast()
            || ! $this->issuanceContext->load(new AuthorizationConsentIdentifier($code->authorization_consent_id));
    }
}
