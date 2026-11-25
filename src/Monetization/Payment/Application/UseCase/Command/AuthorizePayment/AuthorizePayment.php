<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Payment\Application\Exception\ApiException;
use Source\Monetization\Payment\Domain\Exception\InvalidPaymentStatusException;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Shared\Exception\InvalidPaymentMethodException;

readonly class AuthorizePayment implements AuthorizePaymentInterface
{
    public function __construct(
        private PaymentFactoryInterface $paymentFactory,
        private PaymentGatewayInterface $paymentGateway,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    /**
     * @param AuthorizePaymentInputPort $input
     * @param AuthorizePaymentOutputPort $output
     * @return void
     * @throws InvalidPaymentMethodException
     * @throws PaymentGatewayException
     * @throws ApiException
     * @throws InvalidPaymentStatusException
     */
    public function process(AuthorizePaymentInputPort $input, AuthorizePaymentOutputPort $output): void
    {
        $paymentMethod = $this->paymentMethodRepository->findById($input->paymentMethodIdentifier());
        if ($paymentMethod === null
            || (string) $paymentMethod->monetizationAccountIdentifier() !== (string) $input->buyerMonetizationAccountIdentifier()
            || $paymentMethod->status() !== PaymentMethodStatus::ACTIVE) {
            throw new InvalidPaymentMethodException();
        }

        $now = new DateTimeImmutable();

        $payment = $this->paymentFactory->create(
            $input->orderIdentifier(),
            $input->buyerMonetizationAccountIdentifier(),
            $input->money(),
            $input->paymentMethodIdentifier(),
            $now,
        );

        $stripePaymentIntentId = $this->paymentGateway->authorize($payment, $paymentMethod);

        $payment->authorize($now, $stripePaymentIntentId);

        $this->paymentRepository->save($payment);

        $output->setPayment($payment);
        $output->setPaymentMethod($paymentMethod);
    }
}
