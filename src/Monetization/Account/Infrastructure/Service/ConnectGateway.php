<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Service\ConnectGatewayInterface;
use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Stripe\Exception\ApiErrorException;

readonly class ConnectGateway implements ConnectGatewayInterface
{
    public function __construct(
        private StripeClient $stripeClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws StripeConnectException
     */
    public function createConnectedAccount(Email $email, CountryCode $countryCode): StripeConnectedAccountId
    {
        try {
            $stripeClient = $this->stripeClient->client();

            $account = $stripeClient->accounts->create([
                'type' => 'express',
                'country' => $countryCode->value,
                'email' => (string)$email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            $this->logger->info('Stripe Connected Account created', [
                'account_id' => $account->id,
                'email' => (string)$email,
                'country' => $countryCode->value,
            ]);

            return new StripeConnectedAccountId($account->id);
        } catch (ApiErrorException $e) {
            $this->logger->error('Failed to create Stripe Connected Account', [
                'email' => (string)$email,
                'country' => $countryCode->value,
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw new StripeConnectException(
                sprintf('Failed to create connected account: %s', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @throws StripeConnectException
     */
    public function createAccountLink(
        StripeConnectedAccountId $accountId,
        string $refreshUrl,
        string $returnUrl
    ): string {
        try {
            $stripeClient = $this->stripeClient->client();

            $accountLink = $stripeClient->accountLinks->create([
                'account' => (string) $accountId,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            $this->logger->info('Stripe Account Link created', [
                'account_id' => (string) $accountId,
                'url' => $accountLink->url,
            ]);

            return $accountLink->url;
        } catch (ApiErrorException $e) {
            $this->logger->error('Failed to create Stripe Account Link', [
                'account_id' => (string) $accountId,
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw new StripeConnectException(
                sprintf('Failed to create account link: %s', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @throws StripeConnectException
     */
    public function getAccountStatus(StripeConnectedAccountId $accountId): ConnectAccountStatus
    {
        try {
            $stripeClient = $this->stripeClient->client();

            $account = $stripeClient->accounts->retrieve((string) $accountId);

            if (! $account->details_submitted) {
                return ConnectAccountStatus::PENDING;
            }

            if ($account->requirements?->disabled_reason !== null) {
                return ConnectAccountStatus::RESTRICTED;
            }

            $chargesEnabled = $account->charges_enabled ?? false;
            $payoutsEnabled = $account->payouts_enabled ?? false;

            if ($chargesEnabled && $payoutsEnabled) {
                return ConnectAccountStatus::ENABLED;
            }

            return ConnectAccountStatus::RESTRICTED;
        } catch (ApiErrorException $e) {
            $this->logger->error('Failed to retrieve Stripe Account status', [
                'account_id' => (string) $accountId,
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw new StripeConnectException(
                sprintf('Failed to get account status: %s', $e->getMessage()),
                $e
            );
        }
    }
}
