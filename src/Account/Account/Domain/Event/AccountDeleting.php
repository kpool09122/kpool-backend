<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Event;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountDeleting
{
    public function __construct(public AccountIdentifier $accountIdentifier)
    {
    }
}
