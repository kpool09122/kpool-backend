<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ListPaymentMethodsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
