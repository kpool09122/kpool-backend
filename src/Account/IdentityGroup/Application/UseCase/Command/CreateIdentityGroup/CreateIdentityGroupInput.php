<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class CreateIdentityGroupInput implements CreateIdentityGroupInputPort
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
