<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\StripeClient;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;
use Source\Monetization\Account\Application\Service\VerifiedCard;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Stripe\Exception\ApiErrorException;

readonly class CardGateway implements CardGatewayInterface
{
    public function __construct(private StripeClient $stripeClient)
    {
    }

    public function createCustomer(MonetizationAccountIdentifier $accountIdentifier): PaymentCustomerId
    {
        try {
            return new PaymentCustomerId($this->stripeClient->createPaymentCustomer((string) $accountIdentifier));
        } catch (ApiErrorException) {
            throw new CardGatewayException();
        }
    }

    public function prepareRegistration(PaymentCustomerId $customerId): CardRegistrationPreparation
    {
        try {
            $intent = $this->stripeClient->createCardSetupIntent((string) $customerId);
            if ($intent->client_secret === null) {
                throw new CardGatewayException();
            }

            return new CardRegistrationPreparation(new SetupIntentIdentifier($intent->id), $intent->client_secret);
        } catch (ApiErrorException) {
            throw new CardGatewayException();
        }
    }

    public function detach(PaymentCustomerId $customerId, ExternalPaymentMethodId $externalPaymentMethodId): void
    {
        try {
            $method = $this->stripeClient->retrieveAttachedPaymentMethod((string) $externalPaymentMethodId);
            if ($method->customer === null) {
                return;
            }
            if ($method->customer !== (string) $customerId || $method->type !== 'card') {
                throw new CardRegistrationRejectedException();
            }
            $detached = $this->stripeClient->detachPaymentMethod((string) $externalPaymentMethodId);
            if ($detached->customer !== null) {
                throw new CardGatewayException();
            }
        } catch (ApiErrorException) {
            throw new CardGatewayException();
        }
    }

    public function verify(PaymentCustomerId $customerId, SetupIntentIdentifier $setupIntentIdentifier): VerifiedCard
    {
        try {
            $intent = $this->stripeClient->retrieveCardSetupIntent((string) $setupIntentIdentifier);
            if ($intent->status !== 'succeeded' || $intent->customer !== (string) $customerId
                || $intent->usage !== 'off_session' || ! is_string($intent->payment_method)) {
                throw new CardRegistrationRejectedException();
            }
            $method = $this->stripeClient->retrieveAttachedPaymentMethod($intent->payment_method);
            if ($method->customer !== (string) $customerId || $method->type !== 'card' || $method->card === null) {
                throw new CardRegistrationRejectedException();
            }

            return new VerifiedCard(new ExternalPaymentMethodId($method->id), new PaymentMethodMeta(
                $method->card->brand,
                $method->card->last4,
                $method->card->exp_month,
                $method->card->exp_year,
            ));
        } catch (ApiErrorException) {
            // Provider payloads and client secrets must not enter application exception chains.
            throw new CardGatewayException();
        }
    }
}
