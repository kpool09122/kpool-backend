<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Service;

use Application\Http\Client\StripeClient\RetrievePaymentMethod\RetrievePaymentMethodRequest;
use Application\Http\Client\StripeClient\RetrievePaymentMethod\RetrievePaymentMethodResponse;
use Application\Http\Client\StripeClient\StripeClient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Stripe\Exception\InvalidRequestException;
use Tests\TestCase;

class PaymentMethodMetaResolverTest extends TestCase
{
    /**
     * 正常系: Stripe から PaymentMethod のメタ情報を正しく取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testResolveReturnsPaymentMethodMeta(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrievePaymentMethod')
            ->once()
            ->withArgs(static fn (RetrievePaymentMethodRequest $request) => $request->paymentMethodId() === 'pm_test1234567890')
            ->andReturn(new RetrievePaymentMethodResponse(
                id: 'pm_test1234567890',
                type: 'card',
                brand: 'visa',
                last4: '4242',
                expMonth: 12,
                expYear: 2030,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $resolver = $this->app->make(PaymentMethodMetaResolverInterface::class);

        $paymentMethodId = new PaymentMethodId('pm_test1234567890');
        $meta = $resolver->resolve($paymentMethodId);

        $this->assertNotNull($meta);
        $this->assertSame('visa', $meta->brand());
        $this->assertSame('4242', $meta->last4());
        $this->assertSame(12, $meta->expMonth());
        $this->assertSame(2030, $meta->expYear());
    }

    /**
     * 正常系: カード情報が部分的にnullの場合でも正しく取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testResolveReturnsPaymentMethodMetaWithNullFields(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrievePaymentMethod')
            ->once()
            ->andReturn(new RetrievePaymentMethodResponse(
                id: 'pm_test1234567890',
                type: 'card',
                brand: null,
                last4: null,
                expMonth: null,
                expYear: null,
            ));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $resolver = $this->app->make(PaymentMethodMetaResolverInterface::class);

        $meta = $resolver->resolve(new PaymentMethodId('pm_test1234567890'));

        $this->assertNotNull($meta);
        $this->assertNull($meta->brand());
        $this->assertNull($meta->last4());
        $this->assertNull($meta->expMonth());
        $this->assertNull($meta->expYear());
    }

    /**
     * 異常系: Stripe API エラー時に null が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testResolveReturnsNullOnApiError(): void
    {
        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('retrievePaymentMethod')
            ->once()
            ->andThrow(InvalidRequestException::factory('No such payment method', 404));

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $resolver = $this->app->make(PaymentMethodMetaResolverInterface::class);

        $meta = $resolver->resolve(new PaymentMethodId('pm_test1234567890'));

        $this->assertNull($meta);
    }
}
