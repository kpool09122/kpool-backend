<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentTest extends TestCase
{
    /** @return iterable<string, array{PaymentStatus}> */
    public static function statesAllowingMissingPaymentIntentId(): iterable
    {
        yield 'pending' => [PaymentStatus::PENDING];
        yield 'failed' => [PaymentStatus::FAILED];
    }

    /** @return iterable<string, array{PaymentStatus}> */
    public static function statesRequiringPaymentIntentId(): iterable
    {
        yield 'authorized' => [PaymentStatus::AUTHORIZED];
        yield 'captured' => [PaymentStatus::CAPTURED];
        yield 'partially refunded' => [PaymentStatus::PARTIALLY_REFUNDED];
        yield 'refunded' => [PaymentStatus::REFUNDED];
    }

    /** @return iterable<string, array{PaymentStatus}> */
    public static function allPaymentStatuses(): iterable
    {
        foreach (PaymentStatus::cases() as $status) {
            yield $status->value => [$status];
        }
    }

    #[DataProvider('statesAllowingMissingPaymentIntentId')]
    public function testConstructAllowsMissingPaymentIntentIdBeforeAuthorizationOrAfterFailure(PaymentStatus $status): void
    {
        $payment = $this->createDummyPaymentTestData(status: $status)->payment;

        $this->assertSame($status, $payment->status());
        $this->assertNull($payment->stripePaymentIntentId());
    }

    #[DataProvider('statesRequiringPaymentIntentId')]
    public function testConstructRejectsMissingPaymentIntentIdForAuthorizedStates(PaymentStatus $status): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PaymentIntent ID is required unless the payment is pending or failed.');

        $this->createDummyPaymentTestData(status: $status);
    }

    #[DataProvider('allPaymentStatuses')]
    public function testConstructPreservesPaymentIntentIdForEveryStatus(PaymentStatus $status): void
    {
        $intentId = new StripePaymentIntentId('pi_test123456');
        $payment = $this->createDummyPaymentTestData(status: $status, stripePaymentIntentId: $intentId)->payment;

        $this->assertSame($status, $payment->status());
        $this->assertSame($intentId, $payment->stripePaymentIntentId());
    }

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
        $this->assertSame($dummyPayment->method, $payment->paymentMethodIdentifier());
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

        $payment->authorize($authorizedAt, new StripePaymentIntentId('pi_test123456'));

        $this->assertSame(PaymentStatus::AUTHORIZED, $payment->status());
        $this->assertSame($authorizedAt, $payment->authorizedAt());
        $this->assertSame('pi_test123456', (string) $payment->stripePaymentIntentId());
    }

    /**
     * 異常系: すでに与信承認済みの支払いを再度 authorize すると例外となること.
     *
     * @return void
     */
    public function testAuthorizeTwiceThrows(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable(), new StripePaymentIntentId('pi_test123456'));

        $this->expectException(DomainException::class);

        $payment->authorize(new DateTimeImmutable('+1 minute'), new StripePaymentIntentId('pi_test123456'));
    }

    /**
     * 正常系: 与信承認済みの支払いのみ capture（確定） できること.
     *
     * @return void
     */
    public function testCaptureAfterAuthorize(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable(), new StripePaymentIntentId('pi_test123456'));
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
        $this->assertNull($payment->stripePaymentIntentId());
    }

    public function testFailAfterAuthorizationPreservesPaymentIntentId(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $intentId = new StripePaymentIntentId('pi_test123456');
        $payment->authorize(new DateTimeImmutable(), $intentId);

        $payment->fail('card_declined', new DateTimeImmutable());

        $this->assertSame(PaymentStatus::FAILED, $payment->status());
        $this->assertSame($intentId, $payment->stripePaymentIntentId());
    }

    /**
     * 異常系: PendingとAuthorized以外のステータスの場合、fail実行で例外がスローされること.
     *
     * @return void
     */
    public function testFailWhenInvalidStatus(): void
    {
        $payment = $this->createDummyPaymentTestData()->payment;
        $payment->authorize(new DateTimeImmutable(), new StripePaymentIntentId('pi_test123456'));
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
        $payment->authorize(new DateTimeImmutable(), new StripePaymentIntentId('pi_test123456'));
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
        $payment->authorize(new DateTimeImmutable(), new StripePaymentIntentId('pi_test123456'));
        $payment->capture(new DateTimeImmutable('+1 minute'));

        return $payment;
    }

    /**
     * テスト用のダミーPayment情報
     */
    private function createDummyPaymentTestData(
        ?Money $money = null,
        PaymentStatus $status = PaymentStatus::PENDING,
        ?StripePaymentIntentId $stripePaymentIntentId = null,
    ): PaymentTestData {
        $paymentId = new PaymentIdentifier(StrTestHelper::generateUuid());
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money ??= new Money(1000, Currency::JPY);
        $method = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();
        $authorizedAt = new DateTimeImmutable('+1 minute');
        $capturedAt = new DateTimeImmutable('+2 minutes');
        $failedAt = new DateTimeImmutable('+3 minutes');
        $failureReason = 'card_declined';
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
            $refundedAt,
            stripePaymentIntentId: $stripePaymentIntentId,
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
        public PaymentMethodIdentifier $method,
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
