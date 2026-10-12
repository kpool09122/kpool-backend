<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;

readonly class CreatePrincipalGroupInput implements CreatePrincipalGroupInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private string $name,
        private ?PrincipalGroupDescription $description = null,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function description(): ?PrincipalGroupDescription
    {
        return $this->description;
    }

    public function name(): string
    {
        return $this->name;
    }
}
