<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\RetrievePaymentMethod\RetrievePaymentMethodRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Stripe\Exception\ApiErrorException;

readonly class PaymentMethodMetaResolver implements PaymentMethodMetaResolverInterface
{
    public function __construct(
        private StripeClient $stripeClient,
        private LoggerInterface $logger,
    ) {
    }

    public function resolve(PaymentMethodId $paymentMethodId): ?PaymentMethodMeta
    {
        try {
            $request = new RetrievePaymentMethodRequest(
                paymentMethodId: (string) $paymentMethodId,
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
                'payment_method_id' => (string) $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
