<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Repository\SettlementBatchRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreateSettlementBatch;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettlementBatchRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づくSettlementBatchを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();

        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
            'currency' => 'JPY',
            'gross_amount' => 10000,
            'fee_amount' => 1000,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'status' => 'pending',
        ]);

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertNotNull($result);
        $this->assertSame($settlementBatchId, (string) $result->settlementBatchIdentifier());
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame(Currency::JPY, $result->currency());
        $this->assertSame(10000, $result->grossAmount()->amount());
        $this->assertSame(1000, $result->feeAmount()->amount());
        $this->assertSame(9000, $result->netAmount()->amount());
        $this->assertSame(SettlementStatus::PENDING, $result->status());
        $this->assertNull($result->processedAt());
        $this->assertNull($result->paidAt());
        $this->assertNull($result->failedAt());
        $this->assertNull($result->failureReason());
    }

    /**
     * 正常系: 指定したIDを持つSettlementBatchが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $result = $repository->findById(new SettlementBatchIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくステータスに紐づくSettlementBatchを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByStatus(): void
    {
        $monetizationAccountId1 = StrTestHelper::generateUuid();
        $monetizationAccountId2 = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId1);
        CreateMonetizationAccount::create($monetizationAccountId2);

        $settlementBatchId1 = StrTestHelper::generateUuid();
        $settlementBatchId2 = StrTestHelper::generateUuid();
        $settlementBatchId3 = StrTestHelper::generateUuid();

        // PENDING状態のバッチ2つ
        CreateSettlementBatch::create($settlementBatchId1, [
            'monetization_account_id' => $monetizationAccountId1,
            'gross_amount' => 10000,
            'fee_amount' => 1000,
            'status' => 'pending',
        ]);

        CreateSettlementBatch::create($settlementBatchId2, [
            'monetization_account_id' => $monetizationAccountId2,
            'gross_amount' => 20000,
            'fee_amount' => 2000,
            'status' => 'pending',
        ]);

        // FAILED状態のバッチ1つ
        CreateSettlementBatch::create($settlementBatchId3, [
            'monetization_account_id' => $monetizationAccountId1,
            'gross_amount' => 15000,
            'fee_amount' => 1500,
            'status' => 'failed',
            'failed_at' => '2024-02-01 10:00:00',
            'failure_reason' => 'Transfer failed',
        ]);

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);

        $pendingBatches = $repository->findByStatus(SettlementStatus::PENDING);
        $failedBatches = $repository->findByStatus(SettlementStatus::FAILED);
        $paidBatches = $repository->findByStatus(SettlementStatus::PAID);

        $this->assertCount(2, $pendingBatches);
        $this->assertCount(1, $failedBatches);
        $this->assertCount(0, $paidBatches);

        $pendingIds = array_map(fn ($b) => (string) $b->settlementBatchIdentifier(), $pendingBatches);
        $this->assertContains($settlementBatchId1, $pendingIds);
        $this->assertContains($settlementBatchId2, $pendingIds);

        $this->assertSame($settlementBatchId3, (string) $failedBatches[0]->settlementBatchIdentifier());
        $this->assertSame('Transfer failed', $failedBatches[0]->failureReason());
    }

    /**
     * 正常系: 正しくMonetizationAccountIdに紐づくSettlementBatchを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountId(): void
    {
        $monetizationAccountId1 = StrTestHelper::generateUuid();
        $monetizationAccountId2 = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId1);
        CreateMonetizationAccount::create($monetizationAccountId2);

        $settlementBatchId1 = StrTestHelper::generateUuid();
        $settlementBatchId2 = StrTestHelper::generateUuid();
        $settlementBatchId3 = StrTestHelper::generateUuid();

        // monetizationAccountId1に紐づくバッチ2つ
        CreateSettlementBatch::create($settlementBatchId1, [
            'monetization_account_id' => $monetizationAccountId1,
            'gross_amount' => 10000,
            'fee_amount' => 1000,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
        ]);

        CreateSettlementBatch::create($settlementBatchId2, [
            'monetization_account_id' => $monetizationAccountId1,
            'gross_amount' => 15000,
            'fee_amount' => 1500,
            'period_start' => '2024-02-01',
            'period_end' => '2024-02-29',
        ]);

        // monetizationAccountId2に紐づくバッチ1つ
        CreateSettlementBatch::create($settlementBatchId3, [
            'monetization_account_id' => $monetizationAccountId2,
            'gross_amount' => 20000,
            'fee_amount' => 2000,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
        ]);

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);

        $account1Batches = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($monetizationAccountId1)
        );
        $account2Batches = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($monetizationAccountId2)
        );
        $nonExistentAccountBatches = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertCount(2, $account1Batches);
        $this->assertCount(1, $account2Batches);
        $this->assertCount(0, $nonExistentAccountBatches);

        $account1BatchIds = array_map(fn ($b) => (string) $b->settlementBatchIdentifier(), $account1Batches);
        $this->assertContains($settlementBatchId1, $account1BatchIds);
        $this->assertContains($settlementBatchId2, $account1BatchIds);

        $this->assertSame($settlementBatchId3, (string) $account2Batches[0]->settlementBatchIdentifier());
    }

    /**
     * 正常系: 正しく新規のSettlementBatchを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewSettlementBatch(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        $periodStart = new DateTimeImmutable('2024-01-01');
        $periodEnd = new DateTimeImmutable('2024-01-31');

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            $periodStart,
            $periodEnd,
            SettlementStatus::PENDING,
            new Money(50000, Currency::JPY),
            new Money(5000, Currency::JPY),
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        $this->assertDatabaseHas('settlement_batches', [
            'id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'currency' => 'JPY',
            'gross_amount' => 50000,
            'fee_amount' => 5000,
            'net_amount' => 45000,
            'status' => 'pending',
        ]);
    }

    /**
     * 正常系: 正しく既存のSettlementBatchを更新できること（PENDING→PROCESSING→PAID）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingSettlementBatchStatusTransition(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        $periodStart = new DateTimeImmutable('2024-01-01');
        $periodEnd = new DateTimeImmutable('2024-01-31');

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            $periodStart,
            $periodEnd,
            SettlementStatus::PENDING,
            new Money(10000, Currency::JPY),
            new Money(1000, Currency::JPY),
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        // PENDING → PROCESSING
        $processedAt = new DateTimeImmutable('2024-02-01 10:00:00');
        $settlementBatch->markProcessing($processedAt);
        $repository->save($settlementBatch);

        $this->assertDatabaseHas('settlement_batches', [
            'id' => $settlementBatchId,
            'status' => 'processing',
        ]);

        // PROCESSING → PAID
        $paidAt = new DateTimeImmutable('2024-02-01 11:00:00');
        $settlementBatch->markPaid($paidAt);
        $repository->save($settlementBatch);

        $this->assertDatabaseHas('settlement_batches', [
            'id' => $settlementBatchId,
            'status' => 'paid',
        ]);

        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));
        $this->assertSame(SettlementStatus::PAID, $result->status());
        $this->assertNotNull($result->processedAt());
        $this->assertNotNull($result->paidAt());
    }

    /**
     * 正常系: 失敗状態のSettlementBatchを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindFailedSettlementBatch(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        $periodStart = new DateTimeImmutable('2024-01-01');
        $periodEnd = new DateTimeImmutable('2024-01-31');

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            $periodStart,
            $periodEnd,
            SettlementStatus::PENDING,
            new Money(10000, Currency::JPY),
            new Money(1000, Currency::JPY),
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        // 失敗
        $failedAt = new DateTimeImmutable('2024-02-01 10:00:00');
        $settlementBatch->fail('Bank account not found', $failedAt);
        $repository->save($settlementBatch);

        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertSame(SettlementStatus::FAILED, $result->status());
        $this->assertNotNull($result->failedAt());
        $this->assertSame('Bank account not found', $result->failureReason());
        $this->assertNull($result->paidAt());
    }

    /**
     * 正常系: PROCESSING状態のSettlementBatchを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindProcessingSettlementBatch(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        $periodStart = new DateTimeImmutable('2024-01-01');
        $periodEnd = new DateTimeImmutable('2024-01-31');
        $processedAt = new DateTimeImmutable('2024-02-01 10:00:00');

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            $periodStart,
            $periodEnd,
            SettlementStatus::PROCESSING,
            new Money(10000, Currency::JPY),
            new Money(1000, Currency::JPY),
            $processedAt,
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertNotNull($result);
        $this->assertSame(SettlementStatus::PROCESSING, $result->status());
        $this->assertNotNull($result->processedAt());
    }

    /**
     * 正常系: PAID状態のSettlementBatchを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindPaidSettlementBatch(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        $processedAt = new DateTimeImmutable('2024-02-01 10:00:00');
        $paidAt = new DateTimeImmutable('2024-02-01 11:00:00');

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            SettlementStatus::PAID,
            new Money(10000, Currency::JPY),
            new Money(1000, Currency::JPY),
            $processedAt,
            $paidAt,
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertNotNull($result);
        $this->assertSame(SettlementStatus::PAID, $result->status());
        $this->assertNotNull($result->processedAt());
        $this->assertNotNull($result->paidAt());
    }

    /**
     * 正常系: grossAmountとfeeAmountが0の場合も正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithZeroAmounts(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();

        $settlementBatch = new SettlementBatch(
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            Currency::JPY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            SettlementStatus::PENDING,
            new Money(0, Currency::JPY),
            new Money(0, Currency::JPY),
        );

        $repository = $this->app->make(SettlementBatchRepositoryInterface::class);
        $repository->save($settlementBatch);

        $result = $repository->findById(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertNotNull($result);
        $this->assertSame(0, $result->grossAmount()->amount());
        $this->assertSame(0, $result->feeAmount()->amount());
        $this->assertSame(0, $result->netAmount()->amount());
    }
}
