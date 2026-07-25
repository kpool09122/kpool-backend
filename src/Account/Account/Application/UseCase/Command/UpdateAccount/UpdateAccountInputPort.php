<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface UpdateAccountInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function accountName(): AccountName;
}
