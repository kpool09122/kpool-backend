<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods;

use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface ListPaymentMethodsInterface
{
    /**
     * @return list<PaymentMethodReadModel>
     * @throws UnauthorizedException
     */
    public function process(ListPaymentMethodsInputPort $input): array;
}
