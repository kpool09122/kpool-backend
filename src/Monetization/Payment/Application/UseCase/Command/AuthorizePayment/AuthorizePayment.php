<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;

readonly class AuthorizePayment implements AuthorizePaymentInterface
{
    public function __construct(
        private PaymentFactoryInterface $paymentFactory,
        private PaymentGatewayInterface $paymentGateway,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function process(AuthorizePaymentInputPort $input): Payment
    {
        $now = new DateTimeImmutable();

        $payment = $this->paymentFactory->create(
            $input->orderIdentifier(),
            $input->buyerMonetizationAccountIdentifier(),
            $input->money(),
            $input->paymentMethod(),
            $now,
        );

        $this->paymentGateway->authorize($payment);

        $payment->authorize($now);

        $this->paymentRepository->save($payment);

        return $payment;
    }
}
