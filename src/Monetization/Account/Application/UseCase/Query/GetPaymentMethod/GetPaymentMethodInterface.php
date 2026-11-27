<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod;

use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodDetailsReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface GetPaymentMethodInterface
{
    /**
     * @throws UnauthorizedException
     * @throws PaymentMethodNotFoundException
     */
    public function process(GetPaymentMethodInputPort $input): PaymentMethodDetailsReadModel;
}
