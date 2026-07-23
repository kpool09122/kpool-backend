<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface UpdateAccountInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function actorIdentityIdentifier(): IdentityIdentifier;

    public function accountName(): AccountName;
}
