<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Exception\RefundCurrencyMismatchException;
use Source\Monetization\Payment\Domain\Exception\RefundExceedsCapturedAmountException;

interface RefundPaymentInterface
{
    /**
     * @param RefundPaymentInputPort $input
     * @param RefundPaymentOutputPort $output
     * @return void
     * @throws PaymentNotFoundException
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     * @throws RefundCurrencyMismatchException
     * @throws RefundExceedsCapturedAmountException
     */
    public function process(RefundPaymentInputPort $input, RefundPaymentOutputPort $output): void;
}
