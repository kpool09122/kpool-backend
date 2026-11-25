<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Monetization\Account\Application\Exception\PaymentMethodAlreadyExistsException;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class RegisterPaymentMethod implements RegisterPaymentMethodInterface
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentMethodFactoryInterface $paymentMethodFactory,
        private PaymentMethodMetaResolverInterface $paymentMethodMetaResolver,
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PrincipalReachabilityInterface $principalReachability,
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws PaymentMethodAlreadyExistsException
     */
    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        $account = $this->monetizationAccountRepository->findById($input->monetizationAccountIdentifier());
        if ($principal === null || $account === null
            || (string) $principal->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->principalReachability->canAccess($principal->identityIdentifier(), $account->accountIdentifier())
            || ! $this->policyEvaluator->evaluate($principal, Action::PAYMENT_METHOD_REGISTER, new Resource(ResourceType::PAYMENT_METHOD, $account->accountIdentifier()))) {
            throw new UnauthorizedException();
        }

        $existing = $this->paymentMethodRepository->findByExternalPaymentMethodId($input->externalPaymentMethodId(), $account->monetizationAccountIdentifier());

        if ($existing !== null) {
            throw new PaymentMethodAlreadyExistsException();
        }

        $paymentMethod = $this->paymentMethodFactory->create(
            $input->monetizationAccountIdentifier(),
            $input->externalPaymentMethodId(),
            $input->type(),
        );

        $default = $this->paymentMethodRepository->findDefaultByMonetizationAccountId($input->monetizationAccountIdentifier());

        if ($default === null) {
            $paymentMethod->markAsDefault();
        }

        $meta = $this->paymentMethodMetaResolver->resolve($input->externalPaymentMethodId());

        if ($meta !== null) {
            $paymentMethod->updateMeta($meta);
        }

        $this->paymentMethodRepository->save($paymentMethod);

        $output->setPaymentMethod($paymentMethod);
    }
}
