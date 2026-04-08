<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Exception\RefundCurrencyMismatchException;
use Source\Monetization\Payment\Domain\Exception\RefundExceedsCapturedAmountException;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;

readonly class RefundPayment implements RefundPaymentInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PaymentGatewayInterface $paymentGateway,
    ) {
    }

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
    public function process(RefundPaymentInputPort $input, RefundPaymentOutputPort $output): void
    {
        $payment = $this->paymentRepository->findById($input->paymentIdentifier());

        if ($payment === null) {
            throw new PaymentNotFoundException($input->paymentIdentifier());
        }

        $this->paymentGateway->refund($payment, $input->refundAmount(), $input->reason());

        $payment->refund($input->refundAmount(), new DateTimeImmutable(), $input->reason());

        $this->paymentRepository->save($payment);

        $output->setPayment($payment);
    }
}
