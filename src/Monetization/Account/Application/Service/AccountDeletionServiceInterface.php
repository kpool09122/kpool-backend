<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountDeletionServiceInterface
{
    public function delete(AccountIdentifier $accountIdentifier): void;
}
