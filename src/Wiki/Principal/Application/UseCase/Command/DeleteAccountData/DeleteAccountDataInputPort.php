<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DeleteAccountDataInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
