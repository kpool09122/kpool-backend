<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class CreatePrincipalGroupInput implements CreatePrincipalGroupInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private string $name,
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
}
