<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePaymentInput;
use Source\Monetization\Payment\Application\UseCase\Command\CapturePayment\CapturePaymentInterface;
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

class CapturePaymentTest extends TestCase
{
    /**
     * 正常系: AUTHORIZED状態のPaymentをCAPTURED状態に遷移できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCapturesAuthorizedPayment(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUlid());
        $authorizedPayment = $this->createAuthorizedPayment($paymentIdentifier);

        $input = new CapturePaymentInput($paymentIdentifier);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($authorizedPayment);
        $paymentRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Payment $payment) {
                return $payment->status() === PaymentStatus::CAPTURED;
            });

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('capture')
            ->once()
            ->with($authorizedPayment);

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(CapturePaymentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($authorizedPayment, $result);
        $this->assertSame(PaymentStatus::CAPTURED, $result->status());
        $this->assertNotNull($result->capturedAt());
    }

    /**
     * 異常系: Paymentが存在しない場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenPaymentNotFound(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUlid());

        $input = new CapturePaymentInput($paymentIdentifier);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturnNull();
        $paymentRepository->shouldNotReceive('save');

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldNotReceive('capture');

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(CapturePaymentInterface::class);

        $this->expectException(PaymentNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: Gateway例外が伝播すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessPropagatesGatewayException(): void
    {
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUlid());
        $authorizedPayment = $this->createAuthorizedPayment($paymentIdentifier);

        $input = new CapturePaymentInput($paymentIdentifier);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($authorizedPayment);
        $paymentRepository->shouldNotReceive('save');

        $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
        $paymentGateway->shouldReceive('capture')
            ->once()
            ->andThrow(new RuntimeException('Gateway error: Capture failed'));

        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentGatewayInterface::class, $paymentGateway);

        $useCase = $this->app->make(CapturePaymentInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gateway error: Capture failed');

        $useCase->process($input);
    }

    private function createAuthorizedPayment(PaymentIdentifier $paymentIdentifier): Payment
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUlid());
        $money = new Money(1000, Currency::JPY);
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );
        $now = new DateTimeImmutable();

        return new Payment(
            $paymentIdentifier,
            $orderIdentifier,
            $money,
            $paymentMethod,
            $now,
            PaymentStatus::AUTHORIZED,
            $now,
            null,
            null,
            null,
            new Money(0, $money->currency()),
            null,
        );
    }
}
