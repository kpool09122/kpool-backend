<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ProvisionMonetizationAccountInput implements ProvisionMonetizationAccountInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
