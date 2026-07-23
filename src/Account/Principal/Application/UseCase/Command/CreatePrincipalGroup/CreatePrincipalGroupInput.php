<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class CreatePrincipalGroupInput implements CreatePrincipalGroupInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private string $name,
        private AccountRole $role,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function role(): AccountRole
    {
        return $this->role;
    }
}
