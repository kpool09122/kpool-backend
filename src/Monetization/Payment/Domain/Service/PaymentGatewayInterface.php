<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Service;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Shared\Domain\ValueObject\Money;

interface PaymentGatewayInterface
{
    /**
     * @throws PaymentGatewayException
     * @throws ApiException
     */
    public function authorize(Payment $payment, PaymentMethod $paymentMethod): StripePaymentIntentId;

    /**
     * @throws PaymentGatewayException
     * @throws ApiException
     */
    public function capture(Payment $payment): void;

    /**
     * @throws PaymentGatewayException
     * @throws ApiException
     */
    public function refund(Payment $payment, Money $amount, string $reason): void;
}
