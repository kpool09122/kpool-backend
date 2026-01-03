<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteAccount;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DeleteAccountInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
