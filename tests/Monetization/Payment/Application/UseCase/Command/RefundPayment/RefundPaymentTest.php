<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentInterface;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
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

class RefundPaymentTest extends TestCase
{
    /**
     * 正常系: CAPTURED状態のPaymentを部分返金できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessRefundsPartially(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $capturedPayment = $this->createCapturedPayment($paymentIdentifier);
        $refundAmount = new Money(400, Currency::JPY);
        $reason = 'customer_request';

        $input = new RefundPaymentInput($paymentIdentifier, $refundAmount, $reason);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($capturedPayment);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Payment $payment) {
                return $payment->status() === PaymentStatus::PARTIALLY_REFUNDED;
            });

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('refund')
            ->once()
            ->with($capturedPayment, $refundAmount, $reason);

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(RefundPaymentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($capturedPayment, $result);
        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $result->status());
        $this->assertSame($refundAmount->amount(), $result->refundedMoney()->amount());
        $this->assertSame($refundAmount->currency(), $result->refundedMoney()->currency());
        $this->assertSame($reason, $result->lastRefundReason());
    }

    /**
     * 正常系: CAPTURED状態のPaymentを全額返金できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessRefundsFully(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $capturedPayment = $this->createCapturedPayment($paymentIdentifier);
        $refundAmount = new Money(1000, Currency::JPY);
        $reason = 'order_cancelled';

        $input = new RefundPaymentInput($paymentIdentifier, $refundAmount, $reason);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($capturedPayment);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Payment $payment) {
                return $payment->status() === PaymentStatus::REFUNDED;
            });

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('refund')
            ->once()
            ->with($capturedPayment, $refundAmount, $reason);

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(RefundPaymentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($capturedPayment, $result);
        $this->assertSame(PaymentStatus::REFUNDED, $result->status());
        $this->assertSame($refundAmount->amount(), $result->refundedMoney()->amount());
        $this->assertSame($refundAmount->currency(), $result->refundedMoney()->currency());
        $this->assertSame($reason, $result->lastRefundReason());
    }

    /**
     * 異常系: Paymentが存在しない場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenPaymentNotFound(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $refundAmount = new Money(400, Currency::JPY);

        $input = new RefundPaymentInput($paymentIdentifier, $refundAmount, 'test');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturnNull();
        $paymentRepository->shouldNotReceive('save');

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldNotReceive('refund');

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(RefundPaymentInterface::class);

        $this->expectException(PaymentNotFoundException::class);

        $useCase->process($input);
    }

    private function createCapturedPayment(PaymentIdentifier $paymentIdentifier): Payment
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
        $now = new DateTimeImmutable();

        return new Payment(
            $paymentIdentifier,
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $paymentMethod,
            $now,
            PaymentStatus::CAPTURED,
            $now,
            $now,
            null,
            null,
            new Money(0, $money->currency()),
            null,
            null,
        );
    }
}
