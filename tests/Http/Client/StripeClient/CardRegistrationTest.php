<?php

declare(strict_types=1);

namespace Tests\Http\Client\StripeClient;

use Application\Http\Client\StripeClient\StripeClient;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\Helper\PaymentCardFixture;

class CardRegistrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreatesCardOnlyOffSessionSetupIntent(): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->withArgs(function (string $method, string $url, $headers, $params): bool {
            self::assertIsArray($headers);
            self::assertIsArray($params);
            self::assertSame('post', $method);
            self::assertSame('https://api.stripe.com/v1/setup_intents', $url);
            self::assertSame('cus_test123', $params['customer']);
            self::assertSame(['card'], $params['allowed_payment_method_types']);
            self::assertSame('off_session', $params['usage']);

            return true;
        })->andReturn(['{"id":"seti_test123","object":"setup_intent","client_secret":"secret_test"}', 200, []]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            $intent = (new StripeClient('***'))->createCardSetupIntent('cus_test123');
            self::assertSame('seti_test123', $intent->id);
            self::assertSame('secret_test', $intent->client_secret);
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    public function testCreatesCustomerWithStableIdempotencyKey(): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->withArgs(function (string $method, string $url, $headers, $params): bool {
            self::assertIsArray($headers);
            self::assertIsArray($params);
            self::assertSame('post', $method);
            self::assertSame('https://api.stripe.com/v1/customers', $url);
            self::assertContains('Idempotency-Key: account-customer-account123', $headers);
            self::assertIsArray($params['metadata']);
            self::assertSame('account123', $params['metadata']['monetization_account_id']);

            return true;
        })->andReturn(['{"id":"cus_test123","object":"customer"}', 200, []]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            self::assertSame('cus_test123', (new StripeClient('***'))->createPaymentCustomer('account123'));
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }
}
