<?php

declare(strict_types=1);

namespace Tests\Http\Client\StripeClient;

use Application\Http\Client\StripeClient\CreatePaymentIntent\CreatePaymentIntentRequest;
use Application\Http\Client\StripeClient\StripeClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

class StripeClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPaymentIntentUsesEndiveVersionAndSupportedPaymentMethodRestriction(): void
    {
        $originalHttpClient = ApiRequestor::httpClient();
        /** @var ClientInterface&MockInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()
            ->withArgs(function (string $method, string $url, $headers, $params): bool {
                self::assertIsArray($headers);
                self::assertIsArray($params);
                self::assertSame('post', $method);
                self::assertSame('https://api.stripe.com/v1/payment_intents', $url);
                self::assertContains('Stripe-Version: 2026-09-30.endive', $headers);
                self::assertArrayNotHasKey('payment_method_types', $params);
                self::assertSame(['card'], $params['allowed_payment_method_types']);
                self::assertSame('manual', $params['capture_method']);
                self::assertSame('true', $params['confirm']);
                self::assertSame('true', $params['off_session']);
                self::assertSame('pm_test', $params['payment_method']);

                return true;
            })->andReturn([
                '{"id":"pi_test","object":"payment_intent","status":"requires_capture"}',
                200,
                [],
            ]);

        ApiRequestor::setHttpClient($httpClient);

        try {
            $response = (new StripeClient('sk_test_fixture'))->createPaymentIntent(new CreatePaymentIntentRequest(
                amount: 1000,
                currency: 'jpy',
                customerId: 'cus_test',
                paymentMethodId: 'pm_test',
                paymentMethodTypes: ['card'],
            ));

            self::assertSame('pi_test', $response->id());
            self::assertSame('requires_capture', $response->status());
        } finally {
            ApiRequestor::setHttpClient($originalHttpClient);
        }
    }
}
