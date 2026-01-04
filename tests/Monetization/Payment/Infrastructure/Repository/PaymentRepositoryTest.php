<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountCategory;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\AddressLine;
use Source\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Domain\ValueObject\BillingContact;
use Source\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Domain\ValueObject\City;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Domain\ValueObject\ContractName;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentRepositoryTest extends TestCase
{
    /**
     * FK制約を満たすため、事前にAccountとMonetizationAccountを作成するヘルパーメソッド
     */
    private function createMonetizationAccountForTest(string $monetizationAccountId): void
    {
        $accountId = StrTestHelper::generateUuid();
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $ownerIdentityId = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($ownerIdentityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('123-4567'),
            new StateOrProvince('Tokyo'),
            new City('Shibuya'),
            new AddressLine('1-2-3 Shibuya'),
            null,
            null,
        );

        $billingContact = new BillingContact(
            new ContractName('Test Contact'),
            new Email($email),
            null,
        );

        $plan = new Plan(
            new PlanName('Standard Plan'),
            BillingCycle::MONTHLY,
            new PlanDescription(''),
            new Money(0, Currency::JPY),
        );

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            null,
        );

        $contractInfo = new ContractInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::CREDIT_CARD,
            $plan,
            $taxInfo,
            null,
        );

        $memberships = [
            new AccountMembership(
                new IdentityIdentifier($ownerIdentityId),
                AccountRole::OWNER,
            ),
        ];

        $account = new Account(
            new AccountIdentifier($accountId),
            new Email($email),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            $contractInfo,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            $memberships,
            DeletionReadinessChecklist::ready(),
        );

        $this->app->make(AccountRepositoryInterface::class)->save($account);

        $monetizationAccount = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::PURCHASE],
            null,
            null,
        );

        $this->app->make(MonetizationAccountRepositoryInterface::class)->save($monetizationAccount);
    }

    private function createPaymentMethod(): PaymentMethod
    {
        return new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'Visa **** 1234',
            true,
        );
    }

    /**
     * 正常系: 正しくIDに紐づくPaymentを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $paymentMethod = $this->createPaymentMethod();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        $result = $repository->findById(new PaymentIdentifier($paymentId));

        $this->assertNotNull($result);
        $this->assertSame($paymentId, (string) $result->paymentId());
        $this->assertSame($orderId, (string) $result->orderIdentifier());
        $this->assertSame($monetizationAccountId, (string) $result->buyerMonetizationAccountIdentifier());
        $this->assertSame(10000, $result->money()->amount());
        $this->assertSame(Currency::JPY, $result->money()->currency());
        $this->assertSame(PaymentStatus::PENDING, $result->status());
        $this->assertSame((string) $paymentMethod->paymentMethodIdentifier(), (string) $result->paymentMethod()->paymentMethodIdentifier());
        $this->assertSame(PaymentMethodType::CARD, $result->paymentMethod()->type());
        $this->assertSame('Visa **** 1234', $result->paymentMethod()->label());
        $this->assertTrue($result->paymentMethod()->isRecurringEnabled());
        $this->assertNull($result->authorizedAt());
        $this->assertNull($result->capturedAt());
        $this->assertNull($result->failedAt());
        $this->assertNull($result->failureReason());
        $this->assertSame(0, $result->refundedMoney()->amount());
        $this->assertNull($result->lastRefundedAt());
        $this->assertNull($result->lastRefundReason());
    }

    /**
     * 正常系: 指定したIDを持つPaymentが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $result = $repository->findById(new PaymentIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規のPaymentを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewPayment(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $paymentMethod = $this->createPaymentMethod();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(5000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'order_id' => $orderId,
            'buyer_monetization_account_id' => $monetizationAccountId,
            'currency' => 'JPY',
            'amount' => 5000,
            'payment_method_type' => 'card',
            'status' => 'pending',
        ]);
    }

    /**
     * 正常系: 正しく既存のPaymentを更新できること（オーソリ→キャプチャ）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingPaymentAuthorizeAndCapture(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $paymentMethod = $this->createPaymentMethod();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        // オーソリ
        $authorizedAt = new DateTimeImmutable('2024-01-15 10:01:00');
        $payment->authorize($authorizedAt);
        $repository->save($payment);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'authorized',
        ]);

        // キャプチャ
        $capturedAt = new DateTimeImmutable('2024-01-15 10:02:00');
        $payment->capture($capturedAt);
        $repository->save($payment);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'captured',
        ]);

        $result = $repository->findById(new PaymentIdentifier($paymentId));
        $this->assertSame(PaymentStatus::CAPTURED, $result->status());
        $this->assertNotNull($result->authorizedAt());
        $this->assertNotNull($result->capturedAt());
    }

    /**
     * 正常系: 失敗状態のPaymentを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindFailedPayment(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $paymentMethod = $this->createPaymentMethod();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        // 失敗
        $failedAt = new DateTimeImmutable('2024-01-15 10:01:00');
        $payment->fail('Insufficient funds', $failedAt);
        $repository->save($payment);

        $result = $repository->findById(new PaymentIdentifier($paymentId));

        $this->assertSame(PaymentStatus::FAILED, $result->status());
        $this->assertNotNull($result->failedAt());
        $this->assertSame('Insufficient funds', $result->failureReason());
    }

    /**
     * 正常系: 返金済みのPaymentを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindRefundedPayment(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $paymentMethod = $this->createPaymentMethod();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        // オーソリ→キャプチャ
        $payment->authorize(new DateTimeImmutable('2024-01-15 10:01:00'));
        $payment->capture(new DateTimeImmutable('2024-01-15 10:02:00'));
        $repository->save($payment);

        // 一部返金
        $refundedAt = new DateTimeImmutable('2024-01-15 11:00:00');
        $payment->refund(new Money(3000, Currency::JPY), $refundedAt, 'Partial refund requested');
        $repository->save($payment);

        $result = $repository->findById(new PaymentIdentifier($paymentId));

        $this->assertSame(PaymentStatus::PARTIALLY_REFUNDED, $result->status());
        $this->assertSame(3000, $result->refundedMoney()->amount());
        $this->assertNotNull($result->lastRefundedAt());
        $this->assertSame('Partial refund requested', $result->lastRefundReason());

        // 全額返金
        $fullRefundedAt = new DateTimeImmutable('2024-01-15 12:00:00');
        $payment->refund(new Money(7000, Currency::JPY), $fullRefundedAt, 'Full refund completed');
        $repository->save($payment);

        $result = $repository->findById(new PaymentIdentifier($paymentId));

        $this->assertSame(PaymentStatus::REFUNDED, $result->status());
        $this->assertSame(10000, $result->refundedMoney()->amount());
        $this->assertSame('Full refund completed', $result->lastRefundReason());
    }

    /**
     * 正常系: 異なる決済手段タイプのPaymentを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithDifferentPaymentMethodTypes(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $this->createMonetizationAccountForTest($monetizationAccountId);

        $paymentId = StrTestHelper::generateUuid();
        $orderId = StrTestHelper::generateUuid();
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::BANK_TRANSFER,
            'Bank Account ****5678',
            false,
        );

        $payment = new Payment(
            new PaymentIdentifier($paymentId),
            new OrderIdentifier($orderId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(50000, Currency::JPY),
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            null,
        );

        $repository = $this->app->make(PaymentRepositoryInterface::class);
        $repository->save($payment);

        $result = $repository->findById(new PaymentIdentifier($paymentId));

        $this->assertSame(PaymentMethodType::BANK_TRANSFER, $result->paymentMethod()->type());
        $this->assertSame('Bank Account ****5678', $result->paymentMethod()->label());
        $this->assertFalse($result->paymentMethod()->isRecurringEnabled());
    }
}
