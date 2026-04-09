<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentOutput;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\Service\PaymentGatewayInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
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
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );

        $input = new AuthorizePaymentInput($orderIdentifier, $buyerMonetizationAccountIdentifier, $money, $paymentMethod);

        $pendingPayment = $this->createPendingPayment($orderIdentifier, $buyerMonetizationAccountIdentifier, $money, $paymentMethod);

        $paymentFactory = Mockery::mock(PaymentFactoryInterface::class);
        $paymentFactory->shouldReceive('create')
            ->once()
            ->withArgs(fn (OrderIdentifier $oi, MonetizationAccountIdentifier $buyerId, Money $m, PaymentMethod $pm, DateTimeImmutable $createdAt) => $oi === $orderIdentifier && $buyerId === $buyerMonetizationAccountIdentifier && $m === $money && $pm === $paymentMethod)
            ->andReturn($pendingPayment);

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('authorize')
            ->once()
            ->with($pendingPayment);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(fn (Payment $payment) => $payment->status() === PaymentStatus::AUTHORIZED);

        $this->app->instance(PaymentFactoryInterface::class, $paymentFactory);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);

        $useCase = $this->app->make(AuthorizePaymentInterface::class);

        $output = new AuthorizePaymentOutput();
        $useCase->process($input, $output);

        $this->assertSame(PaymentStatus::AUTHORIZED, $pendingPayment->status());
        $this->assertNotNull($pendingPayment->authorizedAt());
        $this->assertNotEmpty($output->toArray());
    }

    private function createPendingPayment(OrderIdentifier $orderIdentifier, MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier, Money $money, PaymentMethod $paymentMethod): Payment
    {
        return new Payment(
            new PaymentIdentifier(StrTestHelper::generateUuid()),
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $paymentMethod,
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
