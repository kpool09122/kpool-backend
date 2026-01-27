<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Infrastructure\Service;

use Application\Http\Client\StripeClient\CapturePaymentIntent\CapturePaymentIntentRequest;
use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentRequest;
use Application\Http\Client\StripeClient\CreateRefund\CreateRefundRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Application\Models\Monetization\Payment as PaymentEloquent;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentGatewayException;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Infrastructure\Exception\StripeApiException;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;

readonly class PaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private StripeClient                           $stripeClient,
        private MonetizationAccountRepositoryInterface $monetizationAccountRepository,
        private LoggerInterface                        $logger,
    ) {
    }

    /**
     * @throws PaymentGatewayException
     */
    public function authorize(Payment $payment): void
    {
        try {
            $monetizationAccount = $this->monetizationAccountRepository->findById(
                $payment->buyerMonetizationAccountIdentifier()
            );

            if ($monetizationAccount === null) {
                throw new PaymentGatewayException('Monetization account not found.');
            }

            $stripeCustomerId = $monetizationAccount->stripeCustomerId();
            if ($stripeCustomerId === null) {
                throw new PaymentGatewayException('Stripe customer not linked to monetization account.');
            }

            $eloquent = PaymentEloquent::query()
                ->where('id', (string) $payment->paymentId())
                ->first();

            if ($eloquent === null) {
                throw new PaymentGatewayException('Payment record not found in database.');
            }

            $stripePaymentMethodId = $eloquent->stripe_payment_method_id;
            if ($stripePaymentMethodId === null) {
                throw new PaymentGatewayException('Stripe payment method not set.');
            }

            $request = new CreatePaymentIntentRequest(
                amount: $this->convertToStripeAmount(
                    $payment->money()->amount(),
                    $payment->money()->currency()
                ),
                currency: strtolower($payment->money()->currency()->value),
                customerId: (string) $stripeCustomerId,
                paymentMethodId: $stripePaymentMethodId,
                paymentMethodTypes: $this->mapPaymentMethodTypes($payment),
                metadata: [
                    'payment_id' => (string) $payment->paymentId(),
                    'order_id' => (string) $payment->orderIdentifier(),
                ],
            );

            $response = $this->stripeClient->createPaymentIntent($request);

            PaymentEloquent::query()
                ->where('id', (string) $payment->paymentId())
                ->update(['stripe_payment_intent_id' => $response->id()]);

            if ($response->status() !== PaymentIntent::STATUS_REQUIRES_CAPTURE) {
                $this->logger->warning('Unexpected PaymentIntent status after authorization', [
                    'payment_id' => (string) $payment->paymentId(),
                    'stripe_payment_intent_id' => $response->id(),
                    'status' => $response->status(),
                ]);

                throw new PaymentGatewayException(
                    sprintf('Authorization failed: unexpected status "%s"', $response->status())
                );
            }

            $this->logger->info('Payment authorized successfully', [
                'payment_id' => (string) $payment->paymentId(),
                'stripe_payment_intent_id' => $response->id(),
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error during authorization', [
                'payment_id' => (string) $payment->paymentId(),
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw StripeApiException::fromStripeException($e);
        }
    }

    /**
     * @throws PaymentGatewayException
     */
    public function capture(Payment $payment): void
    {
        try {
            $eloquent = PaymentEloquent::query()
                ->where('id', (string) $payment->paymentId())
                ->first(['stripe_payment_intent_id']);

            if ($eloquent === null || $eloquent->stripe_payment_intent_id === null) {
                throw new PaymentGatewayException('Stripe Payment Intent not found for this payment.');
            }

            $request = new CapturePaymentIntentRequest(
                paymentIntentId: $eloquent->stripe_payment_intent_id,
                amountToCapture: $this->convertToStripeAmount(
                    $payment->money()->amount(),
                    $payment->money()->currency()
                ),
            );

            $response = $this->stripeClient->capturePaymentIntent($request);

            if ($response->status() !== PaymentIntent::STATUS_SUCCEEDED) {
                throw new PaymentGatewayException(
                    sprintf('Capture failed: unexpected status "%s"', $response->status())
                );
            }

            $this->logger->info('Payment captured successfully', [
                'payment_id' => (string) $payment->paymentId(),
                'stripe_payment_intent_id' => $eloquent->stripe_payment_intent_id,
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error during capture', [
                'payment_id' => (string) $payment->paymentId(),
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw StripeApiException::fromStripeException($e);
        }
    }

    /**
     * @throws PaymentGatewayException
     */
    public function refund(Payment $payment, Money $amount, string $reason): void
    {
        try {
            $eloquent = PaymentEloquent::query()
                ->where('id', (string) $payment->paymentId())
                ->first(['stripe_payment_intent_id']);

            if ($eloquent === null || $eloquent->stripe_payment_intent_id === null) {
                throw new PaymentGatewayException('Stripe Payment Intent not found for this payment.');
            }

            $request = new CreateRefundRequest(
                paymentIntentId: $eloquent->stripe_payment_intent_id,
                amount: $this->convertToStripeAmount(
                    $amount->amount(),
                    $amount->currency()
                ),
                reason: $this->mapRefundReason($reason),
                metadata: [
                    'payment_id' => (string) $payment->paymentId(),
                    'refund_reason' => $reason,
                ],
            );

            $response = $this->stripeClient->createRefund($request);

            if (! in_array($response->status(), ['succeeded', 'pending'], true)) {
                throw new PaymentGatewayException(
                    sprintf('Refund failed: status "%s"', $response->status())
                );
            }

            $this->logger->info('Payment refunded successfully', [
                'payment_id' => (string) $payment->paymentId(),
                'stripe_refund_id' => $response->id(),
                'amount' => $amount->amount(),
                'currency' => $amount->currency()->value,
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error during refund', [
                'payment_id' => (string) $payment->paymentId(),
                'error' => $e->getMessage(),
                'code' => $e->getError()?->code,
            ]);

            throw StripeApiException::fromStripeException($e);
        }
    }

    private function convertToStripeAmount(int $amount, Currency $currency): int
    {
        // JPY and KRW are zero-decimal currencies
        // USD is two-decimal (assuming domain already stores in cents)
        return match ($currency) {
            Currency::JPY, Currency::KRW => $amount,
            Currency::USD => $amount,
        };
    }

    /**
     * @return string[]
     */
    private function mapPaymentMethodTypes(Payment $payment): array
    {
        return match ($payment->paymentMethod()->type()) {
            PaymentMethodType::CARD => ['card'],
            PaymentMethodType::BANK_TRANSFER => ['customer_balance', 'bank_transfer'],
            PaymentMethodType::WALLET => ['link', 'paypal'],
        };
    }

    private function mapRefundReason(string $reason): string
    {
        return 'requested_by_customer';
    }
}
