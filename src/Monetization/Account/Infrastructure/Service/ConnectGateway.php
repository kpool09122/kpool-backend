<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\CreateAccountLink\CreateAccountLinkRequest;
use Application\Http\Client\StripeClient\CreateConnectedAccount\CreateConnectedAccountRequest;
use Application\Http\Client\StripeClient\RetrieveAccount\RetrieveAccountRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Service\ConnectGatewayInterface;
use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
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
    public function createConnectedAccount(Email $email, CountryCode $countryCode): ConnectedAccountId
    {
        try {
            $request = new CreateConnectedAccountRequest(
                email: (string) $email,
                country: $countryCode->value,
            );

            $response = $this->stripeClient->createConnectedAccount($request);

            $this->logger->info('Stripe Connected Account created', [
                'account_id' => $response->id(),
                'email' => (string) $email,
                'country' => $countryCode->value,
            ]);

            return new ConnectedAccountId($response->id());
        } catch (ApiErrorException $e) {
            $this->logger->error('Failed to create Stripe Connected Account', [
                'email' => (string) $email,
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
        ConnectedAccountId $accountId,
        string             $refreshUrl,
        string             $returnUrl
    ): string {
        try {
            $request = new CreateAccountLinkRequest(
                accountId: (string) $accountId,
                refreshUrl: $refreshUrl,
                returnUrl: $returnUrl,
            );

            $response = $this->stripeClient->createAccountLink($request);

            $this->logger->info('Stripe Account Link created', [
                'account_id' => (string) $accountId,
                'url' => $response->url(),
            ]);

            return $response->url();
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
    public function getAccountStatus(ConnectedAccountId $accountId): ConnectAccountStatus
    {
        try {
            $request = new RetrieveAccountRequest(
                accountId: (string) $accountId,
            );

            $response = $this->stripeClient->retrieveAccount($request);

            if (! $response->detailsSubmitted()) {
                return ConnectAccountStatus::PENDING;
            }

            if ($response->disabledReason() !== null) {
                return ConnectAccountStatus::RESTRICTED;
            }

            if ($response->chargesEnabled() && $response->payoutsEnabled()) {
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
