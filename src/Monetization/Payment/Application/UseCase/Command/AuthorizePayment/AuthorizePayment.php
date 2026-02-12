<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
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

    /**
     * @param AuthorizePaymentInputPort $input
     * @param AuthorizePaymentOutputPort $output
     * @return void
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     */
    public function process(AuthorizePaymentInputPort $input, AuthorizePaymentOutputPort $output): void
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

        $output->setPayment($payment);
    }
}
