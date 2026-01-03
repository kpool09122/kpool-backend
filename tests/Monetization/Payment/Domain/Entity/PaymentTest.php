<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $dummyPayment = $this->createDummyPaymentTestData();
        $payment = $dummyPayment->payment;

        $this->assertSame($dummyPayment->paymentId, $payment->paymentId());
        $this->assertSame($dummyPayment->orderIdentifier, $payment->orderIdentifier());
        $this->assertSame($dummyPayment->buyerMonetizationAccountIdentifier, $payment->buyerMonetizationAccountIdentifier());
        $this->assertSame($dummyPayment->money, $payment->money());
        $this->assertSame($dummyPayment->method, $payment->paymentMethod());
        $this->assertSame($dummyPayment->createdAt, $payment->createdAt());
        $this->assertSame($dummyPayment->status, $payment->status());
        $this->assertSame($dummyPayment->refundedMoney->amount(), $payment->refundedMoney()->amount());
        $this->assertSame($dummyPayment->refundedMoney->currency(), $payment->money()->currency());
        $this->assertSame($dummyPayment->authorizedAt, $payment->authorizedAt());
        $this->assertSame($dummyPayment->capturedAt, $payment->capturedAt());
        $this->assertSame($dummyPayment->failedAt, $payment->failedAt());
        $this->assertSame($dummyPayment->failureReason, $payment->failureReason());
        $this->assertSame($dummyPayment->refundedAt, $payment->lastRefundedAt());
    }

    /**
     * 正常系: authorize を呼び出すとステータスが与信承認済みになること.
     *
     * @return void
     */
    public function testAuthorize(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $authorizedAt = new DateTimeImmutable('+1 minute');

        $payment->authorize($authorizedAt);

        $this->assertSame(PaymentStatus::AUTHORIZED, $payment->status());
        $this->assertSame($authorizedAt, $payment->authorizedAt());
    }

    /**
     * 異常系: すでに与信承認済みの支払いを再度 authorize すると例外となること.
     *
     * @return void
     */
    public function testAuthorizeTwiceThrows(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $payment->authorize(new DateTimeImmutable('+1 minute'));
    }

    /**
     * 正常系: 与信承認済みの支払いのみ capture（確定） できること.
     *
     * @return void
     */
    public function testCaptureAfterAuthorize(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable());
        $capturedAt = new DateTimeImmutable('+2 minutes');

        $payment->capture($capturedAt);

        $this->assertSame(PaymentStatus::CAPTURED, $payment->status());
        $this->assertSame($capturedAt, $payment->capturedAt());
    }

    /**
     * 異常系: 未認証状態で capture すると例外となること.
     *
     * @return void
     */
    public function testCaptureWithoutAuthorizeThrows(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;

        $this->expectException(DomainException::class);

        $payment->capture(new DateTimeImmutable());
    }

    /**
     * 正常系: 支払い失敗時に理由と共に状態が failed に更新されること.
     *
     * @return void
     */
    public function testFail(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;

        $payment->fail('card_declined', new DateTimeImmutable());

        $this->assertSame(PaymentStatus::FAILED, $payment->status());
        $this->assertSame('card_declined', $payment->failureReason());
    }

    /**
     * 異常系: PendingとAuthorized以外のステータスの場合、fail実行で例外がスローされること.
     *
     * @return void
     */
    public function testFailWhenInvalidStatus(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable());
        $payment->capture(new DateTimeImmutable());

        $this->expectException(DomainException::class);
        $payment->fail('card_declined', new DateTimeImmutable());
    }

    /**
     * 正常系: capture 後に部分返金/全額返金ができること.
     */
    public function testRefund(): void
    {
        $payment = $this->createDummyPaymentTestData(money: new Money(1000, Currency::JPY))->payment;
        $payment->authorize(new DateTimeImmutable());
        $payment->capture(new DateTimeImmutable('+1 minute'));

        $payment->refund(new Money(400, Currency::JPY), new DateTimeImmutable('+2 minutes'), 'customer_request');
        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $payment->status());
        $this->assertSame(400, $payment->refundedMoney()->amount());
        $this->assertSame('customer_request', $payment->lastRefundReason());

        $payment->refund(new Money(600, Currency::JPY), new DateTimeImmutable('+3 minutes'), 'order_cancelled');
        $this->assertSame(PaymentStatus::REFUNDED, $payment->status());
        $this->assertSame(1000, $payment->refundedMoney()->amount());
        $this->assertSame('order_cancelled', $payment->lastRefundReason());
    }

    /**
     * 異常系: 通貨が一致しない場合は返金できないこと.
     */
    public function testRefundRejectsDifferentCurrency(): void
    {
        $payment = $this->createCapturedPayment();

        $this->expectException(DomainException::class);

        $payment->refund(new Money(100, Currency::USD), new DateTimeImmutable(), 'test');
    }

    /**
     * 異常系: 返金額が支払い額を超える場合は例外となること.
     */
    public function testRefundRejectsOverAmount(): void
    {
        $payment = $this->createCapturedPayment();

        $this->expectException(DomainException::class);

        $payment->refund(new Money(2000, Currency::JPY), new DateTimeImmutable(), 'test');
    }

    /**
     * 異常系: 失敗状態の支払いを返金できないこと.
     */
    public function testRefundRejectsWhenInvalidStatus(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->fail('card_declined', new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $payment->refund(new Money(2000, Currency::JPY), new DateTimeImmutable(), 'test');
    }

    /**
     * 異常系: 失敗状態の支払いを capture できないこと.
     */
    public function testCannotCaptureAfterFail(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->fail('card_declined', new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $payment->capture(new DateTimeImmutable());
    }

    private function createCapturedPayment(): Payment
    {
        $payment = $this->createDummyPaymentTestData(money: new Money(1000, Currency::JPY))->payment;
        $payment->authorize(new DateTimeImmutable());
        $payment->capture(new DateTimeImmutable('+1 minute'));

        return $payment;
    }

    /**
     * テスト用のダミーPayment情報
     */
    private function createDummyPaymentTestData(
        ?Money $money = null,
    ): PaymentTestData {
        $paymentId = new PaymentIdentifier(StrTestHelper::generateUuid());
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money ??= new Money(1000, Currency::JPY);
        $method = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'VISA **** 4242',
            true
        );
        $createdAt = new DateTimeImmutable();
        $authorizedAt = new DateTimeImmutable('+1 minute');
        $capturedAt = new DateTimeImmutable('+2 minutes');
        $failedAt = new DateTimeImmutable('+3 minutes');
        $failureReason = 'card_declined';
        $status = PaymentStatus::PENDING;
        $refundedMoney = new Money(0, $money->currency());
        $refundedAt = new DateTimeImmutable('+2 minutes');

        $payment = new Payment(
            $paymentId,
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $method,
            $createdAt,
            $status,
            $authorizedAt,
            $capturedAt,
            $failedAt,
            $failureReason,
            $refundedMoney,
            $refundedAt
        );

        return new PaymentTestData(
            $paymentId,
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $method,
            $createdAt,
            $status,
            $authorizedAt,
            $capturedAt,
            $failureReason,
            $failedAt,
            $refundedMoney,
            $refundedAt,
            $payment,
        );
    }
}

/**
 * テスト用のPaymentデータ
 */
readonly class PaymentTestData
{
    public function __construct(
        public PaymentIdentifier $paymentId,
        public OrderIdentifier $orderIdentifier,
        public MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        public Money $money,
        public PaymentMethod $method,
        public DateTimeImmutable $createdAt,
        public PaymentStatus $status,
        public ?DateTimeImmutable $authorizedAt,
        public ?DateTimeImmutable $capturedAt,
        public ?string $failureReason,
        public ?DateTimeImmutable $failedAt,
        public Money $refundedMoney,
        public ?DateTimeImmutable $refundedAt,
        public Payment $payment,
    ) {
    }
}
