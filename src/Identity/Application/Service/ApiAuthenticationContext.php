<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

/** Authorization envelope, not an evaluated permission for an individual API operation. */
readonly class ApiAuthenticationContext
{
    private string $sourceTokenIdentifier;
    private AuthorizationConsentIdentifier $consentIdentifier;
    private IdentityIdentifier $identityIdentifier;
    private OAuthClientIdentifier $clientIdentifier;
    /** @var list<AuthorizationScope> */
    private array $scopes;
    private AccountIdentifier $originalAccountIdentifier;
    private AccountPrincipalIdentifier $originalPrincipalIdentifier;

    public function __construct(ValidatedAuthorizationToken $authorizationToken, private DateTimeImmutable $expiresAt)
    {
        $consent = $authorizationToken->consent;
        $this->sourceTokenIdentifier = $authorizationToken->tokenIdentifier;
        $this->consentIdentifier = $consent->identifier();
        $this->identityIdentifier = $consent->identityIdentifier();
        $this->clientIdentifier = $consent->clientIdentifier();
        $this->scopes = $authorizationToken->scopes;
        $this->originalAccountIdentifier = $consent->originalAccountIdentifier();
        $this->originalPrincipalIdentifier = $consent->originalPrincipalIdentifier();
    }

    public function sourceTokenIdentifier(): string
    {
        return $this->sourceTokenIdentifier;
    }

    public function consentIdentifier(): AuthorizationConsentIdentifier
    {
        return $this->consentIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function clientIdentifier(): OAuthClientIdentifier
    {
        return $this->clientIdentifier;
    }

    /** @return list<AuthorizationScope> */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function originalAccountIdentifier(): AccountIdentifier
    {
        return $this->originalAccountIdentifier;
    }

    public function originalPrincipalIdentifier(): AccountPrincipalIdentifier
    {
        return $this->originalPrincipalIdentifier;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
