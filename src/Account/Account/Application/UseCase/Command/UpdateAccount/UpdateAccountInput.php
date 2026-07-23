<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class UpdateAccountInput implements UpdateAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private IdentityIdentifier $actorIdentityIdentifier,
        private AccountName $accountName,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function actorIdentityIdentifier(): IdentityIdentifier
    {
        return $this->actorIdentityIdentifier;
    }

    public function accountName(): AccountName
    {
        return $this->accountName;
    }
}
