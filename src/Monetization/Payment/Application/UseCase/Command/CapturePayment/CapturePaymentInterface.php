<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;

interface CapturePaymentInterface
{
    /**
     * @param CapturePaymentInputPort $input
     * @param CapturePaymentOutputPort $output
     * @return void
     * @throws PaymentNotFoundException
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     */
    public function process(CapturePaymentInputPort $input, CapturePaymentOutputPort $output): void;
}
