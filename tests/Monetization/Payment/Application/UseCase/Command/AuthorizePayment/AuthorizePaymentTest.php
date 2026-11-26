<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentOutput;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AuthorizePaymentTest extends TestCase
{
    /**
     * 正常系: 正しく支払いの与信を取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCreatesAndAuthorizesPayment(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);
        $paymentMethodIdentifier = new PaymentMethodIdentifier(StrTestHelper::generateUuid());

        $input = new AuthorizePaymentInput($orderIdentifier, $buyerMonetizationAccountIdentifier, $money, $paymentMethodIdentifier);

        $pendingPayment = $this->createPendingPayment($orderIdentifier, $buyerMonetizationAccountIdentifier, $money, $paymentMethodIdentifier);

        $paymentFactory = Mockery::mock(PaymentFactoryInterface::class);
        $paymentFactory->shouldReceive('create')
            ->once()
            ->withArgs(fn (OrderIdentifier $oi, MonetizationAccountIdentifier $buyerId, Money $m, PaymentMethodIdentifier $pm, DateTimeImmutable $createdAt) => $oi === $orderIdentifier && $buyerId === $buyerMonetizationAccountIdentifier && $m === $money && $pm === $paymentMethodIdentifier)
            ->andReturn($pendingPayment);

        $paymentMethod = new PaymentMethod($paymentMethodIdentifier, $buyerMonetizationAccountIdentifier, new ExternalPaymentMethodId('pm_1234567890'), PaymentMethodType::CARD, meta: new PaymentMethodMeta('visa', '4242'));

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('authorize')
            ->once()
            ->with($pendingPayment, $paymentMethod)
            ->andReturn(new StripePaymentIntentId('pi_test123456'));

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(fn (Payment $payment) => $payment->status() === PaymentStatus::AUTHORIZED && (string) $payment->stripePaymentIntentId() === 'pi_test123456');

        $this->app()->instance(PaymentFactoryInterface::class, $paymentFactory);
        $this->app()->instance(PaymentGatewayInterface::class, $paymentGateway);
        $this->app()->instance(PaymentRepositoryInterface::class, $paymentRepository);

        $paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $paymentMethodRepository->shouldReceive('findById')->once()->with($paymentMethodIdentifier)->andReturn($paymentMethod);
        $this->app()->instance(PaymentMethodRepositoryInterface::class, $paymentMethodRepository);

        $useCase = $this->app()->make(AuthorizePaymentInterface::class);

        $output = new AuthorizePaymentOutput();
        $useCase->process($input, $output);

        $this->assertSame(PaymentStatus::AUTHORIZED, $pendingPayment->status());
        $this->assertNotNull($pendingPayment->authorizedAt());
        $this->assertSame('visa', $output->toArray()['paymentMethodBrand']);
        $this->assertSame('4242', $output->toArray()['paymentMethodLast4']);
    }

    private function createPendingPayment(OrderIdentifier $orderIdentifier, MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier, Money $money, PaymentMethodIdentifier $paymentMethodIdentifier): Payment
    {
        return new Payment(
            new PaymentIdentifier(StrTestHelper::generateUuid()),
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $paymentMethodIdentifier,
            new DateTimeImmutable(),
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, $money->currency()),
            null,
        );
    }
}
