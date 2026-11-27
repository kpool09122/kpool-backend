<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\StripeClient;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Account\Infrastructure\Service\CardGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\Helper\PaymentCardFixture;

class CardGatewayTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @return iterable<string, array{string,string,string}> */
    public static function invalidIntentCases(): iterable
    {
        foreach (['requires_payment_method','requires_confirmation','requires_action','processing','canceled'] as $status) {
            yield $status => [$status,'cus_test123','off_session'];
        }
        yield 'foreign Customer' => ['succeeded','cus_foreign','off_session'];
        yield 'on session usage' => ['succeeded','cus_test123','on_session'];
    }

    #[DataProvider('invalidIntentCases')]
    public function testRejectsUnsuccessfulOrForeignIntentWithoutReadingCard(string $status, string $customer, string $usage): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->andReturn([json_encode(['id' => 'seti_test123','object' => 'setup_intent','status' => $status,'customer' => $customer,'usage' => $usage,'payment_method' => 'pm_test12345'], JSON_THROW_ON_ERROR),200,[]]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            $this->expectException(CardRegistrationRejectedException::class);
            (new CardGateway(new StripeClient('***')))->verify(new PaymentCustomerId('cus_test123'), new SetupIntentIdentifier('seti_test123'));
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    /** @return iterable<string,array{?string,string}> */
    public static function invalidCardCases(): iterable
    {
        yield 'foreign attachment' => ['cus_foreign','card'];
        yield 'already detached' => [null,'card'];
        yield 'non card' => ['cus_test123','us_bank_account'];
    }

    #[DataProvider('invalidCardCases')]
    public function testRejectsForeignDetachedOrNonCardPaymentMethod(?string $customer, string $type): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->twice()->andReturn(
            ['{"id":"seti_test123","object":"setup_intent","status":"succeeded","customer":"cus_test123","payment_method":"pm_test12345","usage":"off_session"}',200,[]],
            [json_encode(['id' => 'pm_test12345','object' => 'payment_method','customer' => $customer,'type' => $type], JSON_THROW_ON_ERROR),200,[]],
        );
        ApiRequestor::setHttpClient($httpClient);

        try {
            $this->expectException(CardRegistrationRejectedException::class);
            (new CardGateway(new StripeClient('***')))->verify(new PaymentCustomerId('cus_test123'), new SetupIntentIdentifier('seti_test123'));
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    public function testDetachAndRetryAlreadyDetachedWithoutSecondDetach(): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->ordered()->withArgs(fn (string $method, string $url): bool => $method === 'get' && str_ends_with($url, '/payment_methods/pm_test12345'))
            ->andReturn(['{"id":"pm_test12345","object":"payment_method","customer":"cus_test123","type":"card"}',200,[]]);
        $httpClient->shouldReceive('request')->once()->ordered()->withArgs(fn (string $method, string $url): bool => $method === 'post' && str_ends_with($url, '/payment_methods/pm_test12345/detach'))
            ->andReturn(['{"id":"pm_test12345","object":"payment_method","customer":null,"type":"card"}',200,[]]);
        $httpClient->shouldReceive('request')->once()->ordered()->withArgs(fn (string $method, string $url): bool => $method === 'get' && str_ends_with($url, '/payment_methods/pm_test12345'))
            ->andReturn(['{"id":"pm_test12345","object":"payment_method","customer":null,"type":"card"}',200,[]]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            $gateway = new CardGateway(new StripeClient('***'));
            $gateway->detach(new PaymentCustomerId('cus_test123'), new ExternalPaymentMethodId('pm_test12345'));
            $gateway->detach(new PaymentCustomerId('cus_test123'), new ExternalPaymentMethodId('pm_test12345'));
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    public function testProviderErrorDoesNotRetainSecretPayloadInExceptionChain(): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->andReturn(['{"error":{"type":"invalid_request_error","message":"secret_test sk_test_sensitive"}}',400,[]]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            (new CardGateway(new StripeClient('***')))->verify(new PaymentCustomerId('cus_test123'), new SetupIntentIdentifier('seti_test123'));
            self::fail('Expected gateway failure');
        } catch (CardGatewayException $e) {
            self::assertNull($e->getPrevious());
            self::assertStringNotContainsString('sensitive', (string) $e);
            self::assertStringNotContainsString('secret_test', (string) $e);
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    public function testVerifiesSuccessfulIntentAndAttachedCard(): void
    {
        $original = ApiRequestor::httpClient();
        $httpClient = PaymentCardFixture::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')->once()->with('get', 'https://api.stripe.com/v1/setup_intents/seti_test123', Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(['{"id":"seti_test123","object":"setup_intent","status":"succeeded","customer":"cus_test123","payment_method":"pm_test12345","usage":"off_session"}',200,[]]);
        $httpClient->shouldReceive('request')->once()->with('get', 'https://api.stripe.com/v1/payment_methods/pm_test12345', Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn(['{"id":"pm_test12345","object":"payment_method","customer":"cus_test123","type":"card","card":{"brand":"visa","last4":"4242","exp_month":12,"exp_year":2030}}',200,[]]);
        ApiRequestor::setHttpClient($httpClient);

        try {
            $result = (new CardGateway(new StripeClient('***')))->verify(
                new PaymentCustomerId('cus_test123'),
                new SetupIntentIdentifier('seti_test123'),
            );
            self::assertSame('pm_test12345', (string) $result->externalPaymentMethodId);
            self::assertSame('visa', $result->meta->brand());
            self::assertSame('4242', $result->meta->last4());
            self::assertSame(12, $result->meta->expMonth());
            self::assertSame(2030, $result->meta->expYear());
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }
}
