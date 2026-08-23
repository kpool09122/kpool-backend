<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountContextInvalidationServiceInterface
{
    public function forgetByAccountIdentifier(AccountIdentifier $accountIdentifier): void;
}
