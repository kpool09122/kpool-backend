<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class AuthorizationScopeSettingsReadModel
{
    /** @param list<AuthorizationScope> $scopes */
    public function __construct(private array $scopes)
    {
    }

    /** @return array{scopes: list<string>} */
    public function toArray(): array
    {
        return ['scopes' => array_map(static fn (AuthorizationScope $scope): string => $scope->value, $this->scopes)];
    }
}
