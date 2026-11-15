<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
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
     * @param list<AuthorizationTarget> $targets
     */
    public function create(
        IdentityIdentifier $identityIdentifier,
        OAuthClientIdentifier $clientIdentifier,
        array $scopes,
        array $targets,
        AccountIdentifier $originalAccountIdentifier,
        PrincipalIdentifier $originalPrincipalIdentifier,
        AccountIdentifier $initialAccountIdentifier,
        string $resource,
        DateTimeImmutable $consentedAt,
    ): AuthorizationConsent {
        return new AuthorizationConsent(
            new AuthorizationConsentIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            $clientIdentifier,
            $scopes,
            $targets,
            $originalAccountIdentifier,
            $originalPrincipalIdentifier,
            $initialAccountIdentifier,
            $resource,
            $consentedAt,
        );
    }
}
