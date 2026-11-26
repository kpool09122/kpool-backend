<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
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
        private CardGatewayInterface $cardGateway,
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private PolicyEvaluatorInterface $policyEvaluator,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     */
    public function process(RegisterPaymentMethodInputPort $input, RegisterPaymentMethodOutputPort $output): void
    {
        $account = $this->monetizationAccountRepository->findByAccountIdentifier($input->accountIdentifier());
        if ($account === null) {
            throw new UnauthorizedException();
        }
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null
            || (string) $principal->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->principalReachability->canAccess($principal->identityIdentifier(), $account->accountIdentifier())
            || ! $this->policyEvaluator->evaluate($principal, Action::PAYMENT_METHOD_REGISTER, new Resource(ResourceType::PAYMENT_METHOD, $account->accountIdentifier()))) {
            throw new UnauthorizedException();
        }
        $identityIdentifier = $principal->identityIdentifier();
        $this->stepUpAuthenticationStorageService->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION);
        $customerId = $account->stripeCustomerId();
        if ($customerId === null) {
            throw new CardRegistrationRejectedException();
        }
        $card = $this->cardGateway->verify($customerId, $input->setupIntentIdentifier());
        $existing = $this->paymentMethodRepository->findByExternalPaymentMethodId($card->externalPaymentMethodId, $account->monetizationAccountIdentifier());
        if ($existing !== null) {
            $output->setPaymentMethod($existing);

            return;
        }
        $method = $this->paymentMethodFactory->create($account->monetizationAccountIdentifier(), $card->externalPaymentMethodId, PaymentMethodType::CARD);
        $method->updateMeta($card->meta);
        if ($this->paymentMethodRepository->findDefaultByMonetizationAccountId($account->monetizationAccountIdentifier()) === null) {
            $method->markAsDefault();
        }
        $this->paymentMethodRepository->save($method);
        $output->setPaymentMethod($method);
    }
}
