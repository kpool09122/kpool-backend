<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentInterface;
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
        $money = new Money(1000, Currency::JPY);
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );

        $input = new AuthorizePaymentInput($money, $paymentMethod);

        $pendingPayment = $this->createPendingPayment($money, $paymentMethod);

        $paymentFactory = Mockery::mock(PaymentFactoryInterface::class);
        $paymentFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (Money $m, PaymentMethod $pm, DateTimeImmutable $createdAt) use ($money, $paymentMethod) {
                return $m === $money && $pm === $paymentMethod;
            })
            ->andReturn($pendingPayment);

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('authorize')
            ->once()
            ->with($pendingPayment);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Payment $payment) {
                return $payment->status() === PaymentStatus::AUTHORIZED;
            });

        $this->app->instance(PaymentFactoryInterface::class, $paymentFactory);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);

        $useCase = $this->app->make(AuthorizePaymentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($pendingPayment, $result);
        $this->assertSame(PaymentStatus::AUTHORIZED, $result->status());
        $this->assertNotNull($result->authorizedAt());
    }

    private function createPendingPayment(Money $money, PaymentMethod $paymentMethod): Payment
    {
        return new Payment(
            new PaymentIdentifier(StrTestHelper::generateUlid()),
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
