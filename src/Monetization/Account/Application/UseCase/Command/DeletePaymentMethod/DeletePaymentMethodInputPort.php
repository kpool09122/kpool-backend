<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DeletePaymentMethodInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function paymentMethodIdentifier(): PaymentMethodIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
