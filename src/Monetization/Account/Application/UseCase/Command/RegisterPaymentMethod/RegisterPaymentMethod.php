<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\Factory\RegisteredPaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\RegisteredPaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;

readonly class RegisterPaymentMethod implements RegisterPaymentMethodInterface
{
    public function __construct(
        private RegisteredPaymentMethodRepositoryInterface $repository,
        private RegisteredPaymentMethodFactoryInterface $factory,
        private PaymentMethodMetaResolverInterface $metaResolver,
    ) {
    }

    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void
    {
        $existing = $this->repository->findByPaymentMethodId($input->paymentMethodId());

        if ($existing !== null) {
            $output->setRegisteredPaymentMethod($existing);
            $output->setSkipped(true);

            return;
        }

        $paymentMethod = $this->factory->create(
            $input->monetizationAccountIdentifier(),
            $input->paymentMethodId(),
            $input->type(),
        );

        $default = $this->repository->findDefaultByMonetizationAccountId($input->monetizationAccountIdentifier());

        if ($default === null) {
            $paymentMethod->markAsDefault();
        }

        $meta = $this->metaResolver->resolve($input->paymentMethodId());

        if ($meta !== null) {
            $paymentMethod->updateMeta($meta);
        }

        $this->repository->save($paymentMethod);

        $output->setRegisteredPaymentMethod($paymentMethod);
        $output->setSkipped(false);
    }
}
