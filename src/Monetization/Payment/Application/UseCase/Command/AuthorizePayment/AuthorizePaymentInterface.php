<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Shared\Exception\InvalidPaymentMethodException;

interface AuthorizePaymentInterface
{
    /**
     * @param AuthorizePaymentInputPort $input
     * @param AuthorizePaymentOutputPort $output
     * @return void
     * @throws InvalidPaymentMethodException
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     */
    public function process(AuthorizePaymentInputPort $input, AuthorizePaymentOutputPort $output): void;
}
