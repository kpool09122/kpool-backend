<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConsentFactory implements AuthorizationConsentFactoryInterface
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
    ): AuthorizationConsent {
        return new AuthorizationConsent(
            new AuthorizationConsentIdentifier($this->uuidGenerator->generate()),
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
