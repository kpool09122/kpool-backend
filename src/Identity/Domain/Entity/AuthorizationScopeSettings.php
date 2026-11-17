<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use InvalidArgumentException;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AuthorizationScopeSettings
{
    /**
     * @param list<AuthorizationScope> $scopes
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly AuthorizationScopeSettingsIdentifier $identifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private array $scopes,
    ) {
        $this->updateScopes($scopes);
    }

    public function identifier(): AuthorizationScopeSettingsIdentifier
    {
        return $this->identifier;
    }

    /**
     * @param list<AuthorizationScope> $scopes
     * @throws InvalidArgumentException
     */
    public function updateScopes(array $scopes): void
    {
        $values = array_map(static fn (AuthorizationScope $scope): string => $scope->value, $scopes);
        if (! array_is_list($scopes) || count(array_unique($values)) !== count($values)) {
            throw new InvalidArgumentException('Invalid authorization scopes.');
        }
        $this->scopes = $scopes;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    /** @return list<AuthorizationScope> */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
