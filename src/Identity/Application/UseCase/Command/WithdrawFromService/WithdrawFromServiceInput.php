<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawFromService;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class WithdrawFromServiceInput implements WithdrawFromServiceInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private string $confirmationIdentityName,
    ) {
    }

    public function confirmationIdentityName(): string
    {
        return $this->confirmationIdentityName;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
