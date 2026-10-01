<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AccountContextServiceInterface
{
    public function forget(IdentityIdentifier $identityIdentifier): void;

    public function forgetByAccountIdentifier(AccountIdentifier $accountIdentifier): void;
}
