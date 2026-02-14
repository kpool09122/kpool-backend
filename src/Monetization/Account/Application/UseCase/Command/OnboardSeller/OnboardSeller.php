<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Service\ConnectGatewayInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;

readonly class OnboardSeller implements OnboardSellerInterface
{
    public function __construct(
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private ConnectGatewayInterface $connectGateway,
    ) {
    }

    /**
     * @param OnboardSellerInputPort $input
     * @param OnboardSellerOutputPort $output
     * @return void
     * @throws MonetizationAccountNotFoundException
     * @throws CapabilityAlreadyGrantedException
     * @throws StripeConnectException
     */
    public function process(OnboardSellerInputPort $input, OnboardSellerOutputPort $output): void
    {
        $account = $this->monetizationAccountRepository->findById(
            $input->monetizationAccountIdentifier()
        );

        if ($account === null) {
            throw new MonetizationAccountNotFoundException($input->monetizationAccountIdentifier());
        }

        $stripeConnectedAccountId = $account->stripeConnectedAccountId();

        if ($stripeConnectedAccountId === null) {
            $stripeConnectedAccountId = $this->connectGateway->createConnectedAccount(
                $input->email(),
                $input->countryCode(),
            );

            $account->linkStripeConnectedAccount($stripeConnectedAccountId);
            $account->grantCapability(Capability::SELL);
            $account->grantCapability(Capability::RECEIVE_PAYOUT);

            $this->monetizationAccountRepository->save($account);
        }

        $onboardingUrl = $this->connectGateway->createAccountLink(
            $stripeConnectedAccountId,
            $input->refreshUrl(),
            $input->returnUrl()
        );

        $output->setOnboardingUrl($onboardingUrl);
    }
}
