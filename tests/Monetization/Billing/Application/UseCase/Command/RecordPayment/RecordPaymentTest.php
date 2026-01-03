<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInput;
use Source\Monetization\Billing\Application\UseCase\Command\RecordPayment\RecordPaymentInterface;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Exception\InvoiceNotFoundException;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Exception\PaymentNotFoundException;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Shared\Service\PaymentMatcherServiceInterface;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RecordPaymentTest extends TestCase
{
    /**
     * 正常系: PaymentとInvoiceを突合してInvoiceをPAID状態に遷移できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessMatchesPaymentAndInvoice(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);

        $invoice = $this->createIssuedInvoice($invoiceIdentifier, $money);
        $payment = $this->createCapturedPayment($paymentIdentifier, $money);

        $input = new RecordPaymentInput($invoiceIdentifier, $paymentIdentifier);

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('findById')
            ->once()
            ->with($invoiceIdentifier)
            ->andReturn($invoice);
        $invoiceRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Invoice $inv) {
                return $inv->status() === InvoiceStatus::PAID;
            });

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($payment);

        $paymentMatcherService = Mockery::mock(PaymentMatcherServiceInterface::class);
        $paymentMatcherService->shouldReceive('match')
            ->once()
            ->withArgs(function (Invoice $inv, Payment $pay, DateTimeImmutable $paidAt) use ($invoice, $payment) {
                // PaymentMatcherService::match は Invoice の状態を PAID に変更する
                $inv->recordPayment($pay->money(), $paidAt);

                return $inv === $invoice && $pay === $payment;
            });

        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentMatcherServiceInterface::class, $paymentMatcherService);

        $useCase = $this->app->make(RecordPaymentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($invoice, $result);
    }

    /**
     * 異常系: Invoiceが存在しない場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenInvoiceNotFound(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());

        $input = new RecordPaymentInput($invoiceIdentifier, $paymentIdentifier);

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('findById')
            ->once()
            ->with($invoiceIdentifier)
            ->andReturnNull();
        $invoiceRepository->shouldNotReceive('save');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldNotReceive('findById');

        $paymentMatcherService = Mockery::mock(PaymentMatcherServiceInterface::class);
        $paymentMatcherService->shouldNotReceive('match');

        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentMatcherServiceInterface::class, $paymentMatcherService);

        $useCase = $this->app->make(RecordPaymentInterface::class);

        $this->expectException(InvoiceNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: Paymentが存在しない場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenPaymentNotFound(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);

        $invoice = $this->createIssuedInvoice($invoiceIdentifier, $money);

        $input = new RecordPaymentInput($invoiceIdentifier, $paymentIdentifier);

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('findById')
            ->once()
            ->with($invoiceIdentifier)
            ->andReturn($invoice);
        $invoiceRepository->shouldNotReceive('save');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturnNull();

        $paymentMatcherService = Mockery::mock(PaymentMatcherServiceInterface::class);
        $paymentMatcherService->shouldNotReceive('match');

        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentMatcherServiceInterface::class, $paymentMatcherService);

        $useCase = $this->app->make(RecordPaymentInterface::class);

        $this->expectException(PaymentNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: PaymentMatcherServiceが例外を投げた場合は伝播すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessPropagatesMatcherException(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $paymentIdentifier = new PaymentIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);

        $invoice = $this->createIssuedInvoice($invoiceIdentifier, $money);
        $payment = $this->createCapturedPayment($paymentIdentifier, $money);

        $input = new RecordPaymentInput($invoiceIdentifier, $paymentIdentifier);

        $invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $invoiceRepository->shouldReceive('findById')
            ->once()
            ->with($invoiceIdentifier)
            ->andReturn($invoice);
        $invoiceRepository->shouldNotReceive('save');

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findById')
            ->once()
            ->with($paymentIdentifier)
            ->andReturn($payment);

        $paymentMatcherService = Mockery::mock(PaymentMatcherServiceInterface::class);
        $paymentMatcherService->shouldReceive('match')
            ->once()
            ->andThrow(new DomainException('Payment amount does not match invoice total.'));

        $this->app->instance(InvoiceRepositoryInterface::class, $invoiceRepository);
        $this->app->instance(PaymentRepositoryInterface::class, $paymentRepository);
        $this->app->instance(PaymentMatcherServiceInterface::class, $paymentMatcherService);

        $useCase = $this->app->make(RecordPaymentInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Payment amount does not match invoice total.');

        $useCase->process($input);
    }

    private function createIssuedInvoice(InvoiceIdentifier $invoiceIdentifier, Money $total): Invoice
    {
        $now = new DateTimeImmutable();

        return new Invoice(
            $invoiceIdentifier,
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            [new InvoiceLine('Test Product', $total, 1)],
            $total,
            new Money(0, $total->currency()),
            new Money(0, $total->currency()),
            $total,
            $now,
            $now->modify('+14 days'),
            InvoiceStatus::ISSUED,
        );
    }

    private function createCapturedPayment(PaymentIdentifier $paymentIdentifier, Money $money): Payment
    {
        $now = new DateTimeImmutable();
        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );

        return new Payment(
            $paymentIdentifier,
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
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
        );
    }
}
