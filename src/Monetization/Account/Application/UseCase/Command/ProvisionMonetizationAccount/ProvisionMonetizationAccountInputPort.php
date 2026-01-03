<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ProvisionMonetizationAccountInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
