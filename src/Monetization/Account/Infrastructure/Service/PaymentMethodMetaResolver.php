<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\RetrievePaymentMethod\RetrievePaymentMethodRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Stripe\Exception\ApiErrorException;

readonly class PaymentMethodMetaResolver implements PaymentMethodMetaResolverInterface
{
    public function __construct(
        private StripeClient $stripeClient,
        private LoggerInterface $logger,
    ) {
    }

    public function resolve(ExternalPaymentMethodId $externalPaymentMethodId): ?PaymentMethodMeta
    {
        try {
            $request = new RetrievePaymentMethodRequest(
                paymentMethodId: (string) $externalPaymentMethodId,
            );

            $response = $this->stripeClient->retrievePaymentMethod($request);

            return new PaymentMethodMeta(
                brand: $response->brand(),
                last4: $response->last4(),
                expMonth: $response->expMonth(),
                expYear: $response->expYear(),
            );
        } catch (ApiErrorException $e) {
            $this->logger->warning('Failed to retrieve payment method meta from Stripe', [
                'payment_method_id' => (string) $externalPaymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
