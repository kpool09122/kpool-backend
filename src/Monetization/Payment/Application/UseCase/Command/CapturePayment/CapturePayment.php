<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;

readonly class CapturePayment implements CapturePaymentInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
    ) {
    }

    public function process(CapturePaymentInputPort $input): Payment
    {
        $payment = $this->paymentRepository->findById($input->paymentIdentifier());

        if ($payment === null) {
            throw new PaymentNotFoundException($input->paymentIdentifier());
        }

        $this->paymentGateway->capture($payment);

        $payment->capture(new DateTimeImmutable());

        $this->paymentRepository->save($payment);

        return $payment;
    }
}
