<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Factory\AuthorizationConnectionFactoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConnectionFactory implements AuthorizationConnectionFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

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
    ): AuthorizationConnection {
        return new AuthorizationConnection(
            new AuthorizationConnectionIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            $clientIdentifier,
            $scopes,
            $originalAccountIdentifier,
            $originalPrincipalIdentifier,
            $resource,
            $consentedAt,
        );
    }
}
