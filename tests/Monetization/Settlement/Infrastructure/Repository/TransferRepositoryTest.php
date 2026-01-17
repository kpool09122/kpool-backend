<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreateSettlementBatch;
use Tests\Helper\CreateTransfer;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TransferRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づくTransferを取得できること
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
        ]);

        $transferId = StrTestHelper::generateUuid();

        CreateTransfer::create($transferId, [
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'currency' => 'JPY',
            'amount' => 9000,
            'status' => 'pending',
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findById(new TransferIdentifier($transferId));

        $this->assertNotNull($result);
        $this->assertSame($transferId, (string) $result->transferIdentifier());
        $this->assertSame($settlementBatchId, (string) $result->settlementBatchIdentifier());
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame(9000, $result->amount()->amount());
        $this->assertSame(Currency::JPY, $result->amount()->currency());
        $this->assertSame(TransferStatus::PENDING, $result->status());
        $this->assertNull($result->sentAt());
        $this->assertNull($result->failedAt());
        $this->assertNull($result->failureReason());
        $this->assertNull($result->stripeTransferId());
    }

    /**
     * 正常系: 送金済みのTransferを正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithSentTransfer(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        CreateTransfer::create($transferId, [
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'amount' => 15000,
            'status' => 'sent',
            'sent_at' => '2024-02-01 10:00:00',
            'stripe_transfer_id' => 'tr_1234567890abcdef',
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findById(new TransferIdentifier($transferId));

        $this->assertNotNull($result);
        $this->assertSame(TransferStatus::SENT, $result->status());
        $this->assertNotNull($result->sentAt());
        $this->assertNull($result->failedAt());
        $this->assertNull($result->failureReason());
        $this->assertNotNull($result->stripeTransferId());
        $this->assertSame('tr_1234567890abcdef', (string) $result->stripeTransferId());
    }

    /**
     * 正常系: 失敗したTransferを正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithFailedTransfer(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        CreateTransfer::create($transferId, [
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'amount' => 20000,
            'status' => 'failed',
            'failed_at' => '2024-02-01 10:00:00',
            'failure_reason' => 'Insufficient balance',
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findById(new TransferIdentifier($transferId));

        $this->assertNotNull($result);
        $this->assertSame(TransferStatus::FAILED, $result->status());
        $this->assertNull($result->sentAt());
        $this->assertNotNull($result->failedAt());
        $this->assertSame('Insufficient balance', $result->failureReason());
    }

    /**
     * 正常系: 指定したIDを持つTransferが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findById(new TransferIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくSettlementBatchIdに紐づくTransferを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySettlementBatchId(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        CreateTransfer::create($transferId, [
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'amount' => 12000,
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findBySettlementBatchId(new SettlementBatchIdentifier($settlementBatchId));

        $this->assertNotNull($result);
        $this->assertSame($transferId, (string) $result->transferIdentifier());
        $this->assertSame($settlementBatchId, (string) $result->settlementBatchIdentifier());
        $this->assertSame(12000, $result->amount()->amount());
    }

    /**
     * 正常系: 指定したSettlementBatchIdを持つTransferが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySettlementBatchIdWhenNotFound(): void
    {
        $repository = $this->app->make(TransferRepositoryInterface::class);
        $result = $repository->findBySettlementBatchId(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: ペンディング状態のTransferを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingTransfers(): void
    {
        $monetizationAccountId1 = StrTestHelper::generateUuid();
        $monetizationAccountId2 = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId1);
        CreateMonetizationAccount::create($monetizationAccountId2);

        $settlementBatchId1 = StrTestHelper::generateUuid();
        $settlementBatchId2 = StrTestHelper::generateUuid();
        $settlementBatchId3 = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId1, [
            'monetization_account_id' => $monetizationAccountId1,
        ]);
        CreateSettlementBatch::create($settlementBatchId2, [
            'monetization_account_id' => $monetizationAccountId1,
        ]);
        CreateSettlementBatch::create($settlementBatchId3, [
            'monetization_account_id' => $monetizationAccountId2,
        ]);

        $transferId1 = StrTestHelper::generateUuid();
        $transferId2 = StrTestHelper::generateUuid();
        $transferId3 = StrTestHelper::generateUuid();

        // PENDING状態のTransfer 2つ
        CreateTransfer::create($transferId1, [
            'settlement_batch_id' => $settlementBatchId1,
            'monetization_account_id' => $monetizationAccountId1,
            'status' => 'pending',
        ]);

        CreateTransfer::create($transferId2, [
            'settlement_batch_id' => $settlementBatchId2,
            'monetization_account_id' => $monetizationAccountId1,
            'status' => 'pending',
        ]);

        // SENT状態のTransfer 1つ
        CreateTransfer::create($transferId3, [
            'settlement_batch_id' => $settlementBatchId3,
            'monetization_account_id' => $monetizationAccountId2,
            'status' => 'sent',
            'sent_at' => '2024-02-01 10:00:00',
            'stripe_transfer_id' => 'tr_abcdefghij1234',
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $pendingTransfers = $repository->findPendingTransfers();

        $this->assertCount(2, $pendingTransfers);

        $pendingIds = array_map(fn ($t) => (string) $t->transferIdentifier(), $pendingTransfers);
        $this->assertContains($transferId1, $pendingIds);
        $this->assertContains($transferId2, $pendingIds);
        $this->assertNotContains($transferId3, $pendingIds);
    }

    /**
     * 正常系: ペンディング状態のTransferが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingTransfersWhenNoPending(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        CreateTransfer::create($transferId, [
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'status' => 'sent',
            'sent_at' => '2024-02-01 10:00:00',
            'stripe_transfer_id' => 'tr_1234567890',
        ]);

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $pendingTransfers = $repository->findPendingTransfers();

        $this->assertCount(0, $pendingTransfers);
    }

    /**
     * 正常系: 正しく新規のTransferを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewTransfer(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(25000, Currency::JPY),
        );

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $repository->save($transfer);

        $this->assertDatabaseHas('transfers', [
            'id' => $transferId,
            'settlement_batch_id' => $settlementBatchId,
            'monetization_account_id' => $monetizationAccountId,
            'currency' => 'JPY',
            'amount' => 25000,
            'status' => 'pending',
            'sent_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'stripe_transfer_id' => null,
        ]);
    }

    /**
     * 正常系: 正しく既存のTransferを更新できること（PENDING→SENT）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingTransferStatusTransitionToSent(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(30000, Currency::JPY),
        );

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $repository->save($transfer);

        // Stripe Transfer IDを記録
        $transfer->recordStripeTransferId(new StripeTransferId('tr_test1234567890'));

        // PENDING → SENT
        $sentAt = new DateTimeImmutable('2024-02-01 10:00:00');
        $transfer->markSent($sentAt);
        $repository->save($transfer);

        $this->assertDatabaseHas('transfers', [
            'id' => $transferId,
            'status' => 'sent',
            'stripe_transfer_id' => 'tr_test1234567890',
        ]);

        $result = $repository->findById(new TransferIdentifier($transferId));
        $this->assertSame(TransferStatus::SENT, $result->status());
        $this->assertNotNull($result->sentAt());
        $this->assertNotNull($result->stripeTransferId());
    }

    /**
     * 正常系: 正しく既存のTransferを更新できること（PENDING→FAILED）
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingTransferStatusTransitionToFailed(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
        ]);

        $transferId = StrTestHelper::generateUuid();

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(35000, Currency::JPY),
        );

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $repository->save($transfer);

        // PENDING → FAILED
        $failedAt = new DateTimeImmutable('2024-02-01 10:00:00');
        $transfer->markFailed('Bank account not verified', $failedAt);
        $repository->save($transfer);

        $this->assertDatabaseHas('transfers', [
            'id' => $transferId,
            'status' => 'failed',
            'failure_reason' => 'Bank account not verified',
        ]);

        $result = $repository->findById(new TransferIdentifier($transferId));
        $this->assertSame(TransferStatus::FAILED, $result->status());
        $this->assertNotNull($result->failedAt());
        $this->assertSame('Bank account not verified', $result->failureReason());
        $this->assertNull($result->sentAt());
    }

    /**
     * 正常系: 異なる通貨のTransferを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithDifferentCurrency(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($monetizationAccountId);

        $settlementBatchId = StrTestHelper::generateUuid();
        CreateSettlementBatch::create($settlementBatchId, [
            'monetization_account_id' => $monetizationAccountId,
            'currency' => 'USD',
        ]);

        $transferId = StrTestHelper::generateUuid();

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(100, Currency::USD),
        );

        $repository = $this->app->make(TransferRepositoryInterface::class);
        $repository->save($transfer);

        $result = $repository->findById(new TransferIdentifier($transferId));

        $this->assertNotNull($result);
        $this->assertSame(100, $result->amount()->amount());
        $this->assertSame(Currency::USD, $result->amount()->currency());
    }
}
