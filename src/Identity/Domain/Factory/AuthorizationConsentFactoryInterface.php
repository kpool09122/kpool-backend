<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationConsentFactoryInterface
{
    /**
     * @param list<AuthorizationScope> $scopes
     * @param list<AuthorizationTarget> $targets
     */
    public function create(
        IdentityIdentifier $identityIdentifier,
        OAuthClientIdentifier $clientIdentifier,
        array $scopes,
        array $targets,
        AccountIdentifier $originalAccountIdentifier,
        AccountPrincipalIdentifier $originalPrincipalIdentifier,
        AccountIdentifier $initialAccountIdentifier,
        string $resource,
        DateTimeImmutable $consentedAt,
    ): AuthorizationConsent;
}
