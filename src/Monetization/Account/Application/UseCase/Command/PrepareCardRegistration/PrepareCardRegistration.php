<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Domain\Exception\PaymentCustomerAlreadyLinkedException;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class PrepareCardRegistration implements PrepareCardRegistrationInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private PolicyEvaluatorInterface $policyEvaluator,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
        private CardGatewayInterface $cardGateway,
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws StepUpAuthenticationRequiredException
     * @throws CardGatewayException
     * @throws PaymentCustomerAlreadyLinkedException
     */
    public function process(PrepareCardRegistrationInputPort $input, PrepareCardRegistrationOutputPort $output): void
    {
        $account = $this->monetizationAccountRepository->findByAccountIdentifier($input->accountIdentifier());
        if ($account === null) {
            throw new UnauthorizedException();
        }
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null
            || (string) $principal->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->principalReachability->canAccess($principal->identityIdentifier(), $account->accountIdentifier())
            || ! $this->policyEvaluator->evaluate($principal, Action::CARD_REGISTRATION_PREPARE, new Resource(ResourceType::PAYMENT_METHOD, $account->accountIdentifier()))) {
            throw new UnauthorizedException();
        }
        $identityIdentifier = $principal->identityIdentifier();
        $this->stepUpAuthenticationStorageService->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION);
        $customerId = $account->stripeCustomerId();
        if ($customerId === null) {
            $customerId = $this->cardGateway->createCustomer($account->monetizationAccountIdentifier());
            $account->linkStripeCustomer($customerId);
            $this->monetizationAccountRepository->save($account);
        }
        $output->setPreparation($this->cardGateway->prepareRegistration($customerId));
    }
}
