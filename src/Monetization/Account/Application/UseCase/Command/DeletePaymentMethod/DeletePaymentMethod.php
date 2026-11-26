<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

use DateTimeImmutable;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Domain\Factory\PaymentMethodArchiveFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodArchiveRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class DeletePaymentMethod implements DeletePaymentMethodInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private PolicyEvaluatorInterface $policyEvaluator,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
        private CardGatewayInterface $cardGateway,
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
        private PaymentMethodArchiveRepositoryInterface $paymentMethodArchiveRepository,
        private PaymentMethodArchiveFactoryInterface $paymentMethodArchiveFactory,
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws CardRegistrationRejectedException
     * @throws PaymentMethodNotFoundException
     */
    public function process(DeletePaymentMethodInputPort $input, DeletePaymentMethodOutputPort $output): void
    {
        $account = $this->monetizationAccountRepository->findByAccountIdentifier($input->accountIdentifier());
        if ($account === null) {
            throw new UnauthorizedException();
        }
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null
            || (string) $principal->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->principalReachability->canAccess($principal->identityIdentifier(), $account->accountIdentifier())
            || ! $this->policyEvaluator->evaluate($principal, Action::PAYMENT_METHOD_DELETE, new Resource(ResourceType::PAYMENT_METHOD, $account->accountIdentifier()))) {
            throw new UnauthorizedException();
        }
        $identityIdentifier = $principal->identityIdentifier();
        $this->stepUpAuthenticationStorageService->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION);
        $method = $this->paymentMethodRepository->findById($input->paymentMethodIdentifier());
        if ($method === null) {
            $archive = $this->paymentMethodArchiveRepository->findByPaymentMethodIdentifier($input->paymentMethodIdentifier());
            if ($archive !== null && (string) $archive->monetizationAccountIdentifier === (string) $account->monetizationAccountIdentifier()) {
                return;
            }

            throw new PaymentMethodNotFoundException();
        }
        if ((string) $method->monetizationAccountIdentifier() !== (string) $account->monetizationAccountIdentifier()) {
            throw new PaymentMethodNotFoundException();
        }
        $customerId = $account->stripeCustomerId();
        if ($customerId === null) {
            throw new CardRegistrationRejectedException();
        }
        $this->cardGateway->detach($customerId, $method->externalPaymentMethodId());
        $this->paymentMethodArchiveRepository->save($this->paymentMethodArchiveFactory->create($method, new DateTimeImmutable()));
        $this->paymentMethodRepository->delete($method->paymentMethodIdentifier());
        if ($method->isDefault()) {
            foreach ($this->paymentMethodRepository->findByMonetizationAccountId($account->monetizationAccountIdentifier()) as $remaining) {
                if ($remaining->status() === PaymentMethodStatus::ACTIVE) {
                    $remaining->markAsDefault();
                    $this->paymentMethodRepository->save($remaining);

                    break;
                }
            }
        }
    }
}
