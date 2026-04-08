<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
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

    /**
     * @param CapturePaymentInputPort $input
     * @param CapturePaymentOutputPort $output
     * @return void
     * @throws PaymentNotFoundException
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     */
    public function process(CapturePaymentInputPort $input, CapturePaymentOutputPort $output): void
    {
        $payment = $this->paymentRepository->findById($input->paymentIdentifier());

        if ($payment === null) {
            throw new PaymentNotFoundException($input->paymentIdentifier());
        }

        $this->paymentGateway->capture($payment);

        $payment->capture(new DateTimeImmutable());

        $this->paymentRepository->save($payment);

        $output->setPayment($payment);
    }
}
