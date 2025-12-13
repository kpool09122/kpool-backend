<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;

readonly class RefundPayment implements RefundPaymentInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
    ) {
    }

    public function process(RefundPaymentInputPort $input): Payment
    {
        $payment = $this->paymentRepository->findById($input->paymentIdentifier());

        if ($payment === null) {
            throw new PaymentNotFoundException($input->paymentIdentifier());
        }

        $this->paymentGateway->refund($payment, $input->refundAmount(), $input->reason());

        $payment->refund($input->refundAmount(), new DateTimeImmutable(), $input->reason());

        $this->paymentRepository->save($payment);

        return $payment;
    }
}
