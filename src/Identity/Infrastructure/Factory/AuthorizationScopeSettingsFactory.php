<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use InvalidArgumentException;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Factory\AuthorizationScopeSettingsFactoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationScopeSettingsFactory implements AuthorizationScopeSettingsFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /**
     * @param list<AuthorizationScope> $scopes
     * @throws InvalidArgumentException
     */
    public function create(IdentityIdentifier $identityIdentifier, array $scopes): AuthorizationScopeSettings
    {
        return new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier($this->uuidGenerator->generate()), $identityIdentifier, $scopes);
    }
}
