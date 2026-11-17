<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationConnectionFactoryInterface
{
    /**
     * @param list<AuthorizationScope> $scopes
     */
    public function create(
        IdentityIdentifier $identityIdentifier,
        OAuthClientIdentifier $clientIdentifier,
        array $scopes,
        AccountIdentifier $originalAccountIdentifier,
        AccountPrincipalIdentifier $originalPrincipalIdentifier,
        string $resource,
        DateTimeImmutable $consentedAt,
    ): AuthorizationConnection;
}
