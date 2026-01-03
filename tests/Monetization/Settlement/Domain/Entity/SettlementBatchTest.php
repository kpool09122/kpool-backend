<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;

class SettlementBatchTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount();
        $start = new DateTimeImmutable('now');
        $end = new DateTimeImmutable('now');
        $status = SettlementStatus::PROCESSING;
        $grossAmount = new Money(10000, Currency::JPY);
        $feeAmount = new Money(10000, Currency::JPY);
        $processedAt = new DateTimeImmutable('now');
        $batch = $this->createBatch([
            'identifier' => $identifier,
            'account' => $account,
            'start' => $start,
            'end' => $end,
            'status' => $status,
            'grossAmount' => $grossAmount,
            'feeAmount' => $feeAmount,
            'processedAt' => $processedAt,
        ]);
        $this->assertSame($identifier, $batch->settlementBatchIdentifier());
        $this->assertSame($account, $batch->settlementAccount());
        $this->assertSame($start, $batch->periodStart());
        $this->assertSame($end, $batch->periodEnd());
        $this->assertSame($status, $batch->status());
        $this->assertSame($grossAmount, $batch->grossAmount());
        $this->assertSame($feeAmount, $batch->feeAmount());
        $this->assertSame($processedAt, $batch->processedAt());
        $this->assertNull($batch->paidAt());
        $this->assertNull($batch->failedAt());
        $this->assertNull($batch->failureReason());
    }

    /**
     * 異常系: Pendingステータス時にprocessedAtなどの不要な時刻を持っていると例外になること.
     *
     * @return void
     */
    public function testConstructWhenPendingWithUnnecessaryProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PENDING,
            'processedAt' => new DateTimeImmutable('now'),
        ]);
    }

    /**
     * 異常系: 処理中ステータス時にprocessedAtがないと例外になること.
     *
     * @return void
     */
    public function testConstructWhenProcessingWithoutProcessedAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => null,
        ]);
    }

    /**
     * 異常系: 処理中ステータス時にpaidAtなどの不要な時刻を持っていると例外になること.
     *
     * @return void
     */
    public function testConstructWhenProcessingWithUnnecessaryProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('now'),
            'paidAt' => new DateTimeImmutable('now'),
        ]);
    }

    /**
     * 異常系: 処理中ステータス時に送金情報を持っていると例外になること.
     *
     * @return void
     */
    public function testConstructWhenProcessingWithTransfer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('now'),
            'transfer' => $this->createTransfer(),
        ]);
    }

    /**
     * 異常系: 支払済ステータス時にprocessedAtなどの必要な情報がないと、例外になること.
     *
     * @return void
     */
    public function testConstructWhenPaidWithoutNecessaryProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('now'),
            'paidAt' => null,
            'transfer' => $this->createTransfer(),

        ]);
    }

    /**
     * 異常系: 支払済ステータス時に失敗情報があると、例外になること.
     *
     * @return void
     */
    public function testConstructWhenPaidWithFailureInfo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('now'),
            'paidAt' => new DateTimeImmutable('now'),
            'transfer' => $this->createTransfer(),
            'failedAt' => new DateTimeImmutable('now'),
        ]);
    }

    /**
     * 異常系: 失敗ステータス時に失敗時刻がないと、例外になること.
     *
     * @return void
     */
    public function testConstructWhenPaidWithoutFailuredAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::FAILED,
            'failedAt' => null,
        ]);
    }

    /**
     * 異常系: 失敗ステータス時に支払い時刻があると、例外になること.
     *
     * @return void
     */
    public function testConstructWhenPaidWithPaidAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::FAILED,
            'paidAt' => new DateTimeImmutable('now'),
            'failedAt' => new DateTimeImmutable('now'),
            'failureReason' => 'Sending Error',
        ]);
    }

    /**
     * 異常系: 失敗ステータス時に送金情報があると、例外になること.
     *
     * @return void
     */
    public function testConstructWhenPaidWithTransfer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::FAILED,
            'paidAt' => null,
            'transfer' => $this->createTransfer(),
            'failedAt' => new DateTimeImmutable('now'),
            'failureReason' => 'Sending Error',
        ]);
    }

    /**
     * 異常系: 期間の開始が終了より遅い場合は例外がスローされること..
     *
     * @return void
     */
    public function testWhenStartPeriodLaterThanEndPeriod(): void
    {
        $this->expectException(DomainException::class);
        $start = new DateTimeImmutable('now');
        $end = new DateTimeImmutable('-1 day');
        $this->createBatch([
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * 異常系: 支払い済みステータスで転送情報が欠けている場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructPaidWithoutTransfer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'grossAmount' => new Money(100, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 失敗ステータスで理由が空の場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructFailedWithoutReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBatch([
            'status' => SettlementStatus::FAILED,
            'failedAt' => new DateTimeImmutable('2024-01-18'),
        ]);
    }

    /**
     * 異常系: 支払い済みステータスでアカウントが一致しない転送を保持している場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructPaidWithTransferFromDifferentAccount(): void
    {
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $batchAccount = $this->createAccount();
        $otherAccount = $this->createAccount();
        $transfer = $this->createTransferMock(
            identifier: $identifier,
            account: $otherAccount,
            amount: new Money(100, Currency::JPY),
            status: TransferStatus::SENT,
            sentAt: new DateTimeImmutable('2024-01-17'),
        );

        $this->expectException(InvalidArgumentException::class);

        $this->createBatch([
            'identifier' => $identifier,
            'account' => $batchAccount,
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'grossAmount' => new Money(100, Currency::JPY),
            'transfer' => $transfer,
        ]);
    }

    /**
     * 異常系: 支払い済みステータスで清算額と一致しない転送額の場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructPaidWithTransferAmountMismatch(): void
    {
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount();
        $transfer = $this->createTransferMock(
            identifier: $identifier,
            account: $account,
            amount: new Money(50, Currency::JPY),
            status: TransferStatus::SENT,
            sentAt: new DateTimeImmutable('2024-01-17'),
        );

        $this->expectException(InvalidArgumentException::class);

        $this->createBatch([
            'identifier' => $identifier,
            'account' => $account,
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'grossAmount' => new Money(100, Currency::JPY),
            'transfer' => $transfer,
        ]);
    }

    /**
     * 異常系: 支払い済みステータスで送金済みでない転送を保持している場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructPaidWithUnsentTransfer(): void
    {
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount();
        $transfer = $this->createTransferMock(
            identifier: $identifier,
            account: $account,
            amount: new Money(100, Currency::JPY),
            status: TransferStatus::PENDING,
            sentAt: null,
        );

        $this->expectException(InvalidArgumentException::class);

        $this->createBatch([
            'identifier' => $identifier,
            'account' => $account,
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'grossAmount' => new Money(100, Currency::JPY),
            'transfer' => $transfer,
        ]);
    }

    /**
     * 異常系: 支払い済みステータスで送金時刻が欠けた転送を保持している場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructPaidWithTransferWithoutSentAt(): void
    {
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $account = $this->createAccount();
        $transfer = $this->createTransferMock(
            identifier: $identifier,
            account: $account,
            amount: new Money(100, Currency::JPY),
            status: TransferStatus::SENT,
            sentAt: null,
        );

        $this->expectException(InvalidArgumentException::class);

        $this->createBatch([
            'identifier' => $identifier,
            'account' => $account,
            'status' => SettlementStatus::PAID,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'grossAmount' => new Money(100, Currency::JPY),
            'transfer' => $transfer,
        ]);
    }

    /**
     * 正常系: 売上追加から手数料適用、送金完了までの流れを通せること.
     *
     * @return void
     */
    public function testHappyPath(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
        ]);

        $batch->recordRevenue(new Money(5000, Currency::JPY));
        $batch->recordRevenue(new Money(2000, Currency::JPY));
        $batch->applyFee(new Money(700, Currency::JPY));

        $batch->markProcessing(new DateTimeImmutable('2024-01-16'));

        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(6300, Currency::JPY)
        );
        $batch->attachTransfer($transfer);

        $transfer->markSent(new DateTimeImmutable('2024-01-17'));
        $batch->markPaid($transfer);

        $this->assertSame(7000, $batch->grossAmount()->amount());
        $this->assertSame(700, $batch->feeAmount()->amount());
        $this->assertSame(6300, $batch->netAmount()->amount());
        $this->assertSame(SettlementStatus::PAID, $batch->status());
        $this->assertEquals(new DateTimeImmutable('2024-01-17'), $batch->paidAt());
        $this->assertSame($transfer, $batch->transfer());
    }

    /**
     * 異常系: 送金ステータスがPendingでない場合に処理中に変更しようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testMarkedProcessingWhenInvalidStatus(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $this->expectException(DomainException::class);
        $batch->markProcessing(new DateTimeImmutable('2024-01-16'));
    }

    /**
     * 異常系: 送金時に清算ステータスが処理中でない場合は、例外がスローされること.
     *
     * @return void
     */
    public function testAttachTransferWhenInvalidStatus(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(6300, Currency::JPY),
            TransferStatus::PENDING,
        );
        $this->expectException(DomainException::class);
        $batch->attachTransfer($transfer);
    }

    /**
     * 異常系: バッチIDが異なる場合は、例外がスローされること.
     *
     * @return void
     */
    public function testAttachTransferWhenDifferentBatchIdentifier(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $transfer = $this->createTransfer(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $account,
            new Money(6300, Currency::JPY),
            TransferStatus::PENDING,
        );
        $this->expectException(DomainException::class);
        $batch->attachTransfer($transfer);
    }

    /**
     * 異常系: アカウントIDが異なる場合は、例外がスローされること.
     *
     * @return void
     */
    public function testAttachTransferWhenDifferentAccountIdentifier(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $this->createAccount(),
            new Money(6300, Currency::JPY),
        );
        $this->expectException(DomainException::class);
        $batch->attachTransfer($transfer);
    }

    /**
     * 異常系: 送金額が異なる場合は、例外がスローされること.
     *
     * @return void
     */
    public function testAttachTransferWhenDifferentCurrency(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(100, Currency::JPY),
        );
        $this->expectException(DomainException::class);
        $batch->attachTransfer($transfer);
    }

    /**
     * 異常系: 送金処理中以外のバッチで支払い済みにしようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testMarkedPaidWhenInvalidStatus(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PENDING,
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(100, Currency::JPY),
        );
        $this->expectException(DomainException::class);
        $batch->markPaid($transfer);
    }

    /**
     * 異常系: 送金ステータスが送金済みでない時に支払い済みに更新しようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testMarkedPaidWhenStillNotSent(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(100, Currency::JPY),
            TransferStatus::PENDING
        );
        $this->expectException(DomainException::class);
        $batch->markPaid($transfer);
    }

    /**
     * 異常系: 送金時間がない時に支払い済みに更新しようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testMarkedPaidWhenNoExistSentAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            status: TransferStatus::SENT,
            sentAt: null,
        );
    }

    /**
     * 異常系: バッチに他の送金がアタッチされていた場合、例外がスローされること.
     *
     * @return void
     */
    public function testMarkedPaidWhenAlreadyAttachedTransfer(): void
    {
        $account = $this->createAccount();
        $batch = $this->createBatch([
            'identifier' => new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            'account' => $account,
            'start' => new DateTimeImmutable('2024-01-01'),
            'end' => new DateTimeImmutable('2024-01-15'),
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);
        $transfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(100, Currency::JPY),
            TransferStatus::SENT,
            new DateTimeImmutable('2024-01-01'),
        );
        $anotherTransfer = $this->createTransfer(
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(0, Currency::JPY),
            TransferStatus::SENT,
            new DateTimeImmutable('2024-01-01'),
        );
        $batch->attachTransfer($anotherTransfer);
        $this->expectException(DomainException::class);
        $batch->markPaid($transfer);
    }

    /**
     * 正常系: 失敗ステータスへ更新され、理由と日時が保持されること.
     *
     * @return void
     */
    public function testFail(): void
    {
        $failedAt = new DateTimeImmutable('2024-01-18 12:00:00');
        $batch = $this->createBatch([
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);

        $batch->fail('Bank returned error', $failedAt);

        $this->assertSame(SettlementStatus::FAILED, $batch->status());
        $this->assertSame('Bank returned error', $batch->failureReason());
        $this->assertSame($failedAt, $batch->failedAt());
    }

    /**
     * 異常系: 支払い済みのバッチを失敗に更新しようとすると例外となること.
     *
     * @return void
     */
    public function testFailWhenAlreadyPaid(): void
    {
        $account = $this->createAccount();
        $identifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $batch = $this->createBatch([
            'identifier' => $identifier,
            'account' => $account,
            'status' => SettlementStatus::PAID,
            'paidAt' => new DateTimeImmutable('2024-01-17'),
            'processedAt' => new DateTimeImmutable('2024-01-16'),
            'grossAmount' => new Money(100, Currency::JPY),
            'transfer' => $this->createTransfer(
                identifier: $identifier,
                account: $account,
                amount: new Money(100, Currency::JPY),
                status: TransferStatus::SENT,
                sentAt: new DateTimeImmutable('2024-01-17'),
            ),
        ]);

        $this->expectException(DomainException::class);

        $batch->fail('Payment already complete', new DateTimeImmutable('2024-01-18'));
    }

    /**
     * 異常系: 空文字の理由で失敗に更新しようとすると例外となること.
     *
     * @return void
     */
    public function testFailWithEmptyReason(): void
    {
        $batch = $this->createBatch([
            'status' => SettlementStatus::PROCESSING,
            'processedAt' => new DateTimeImmutable('2024-01-16'),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $batch->fail('   ', new DateTimeImmutable('2024-01-18'));
    }

    /**
     * 異常系: 通貨が異なる売上を追加しようとすると例外となること.
     *
     * @return void
     */
    public function testRejectsDifferentCurrencyRevenue(): void
    {
        $batch = new SettlementBatch(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $this->createAccount(),
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-15')
        );

        $this->expectException(DomainException::class);

        $batch->recordRevenue(new Money(1000, Currency::USD));
    }

    /**
     * 異常系: 手数料が売上を超える場合は例外となること.
     *
     * @return void
     */
    public function testRejectsFeeOverGross(): void
    {
        $batch = new SettlementBatch(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $this->createAccount(),
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-15')
        );
        $batch->recordRevenue(new Money(1000, Currency::JPY));

        $this->expectException(DomainException::class);

        $batch->applyFee(new Money(2000, Currency::JPY));
    }

    /**
     * @param array<string, mixed> $values
     * @return SettlementBatch
     */
    private function createBatch(
        array $values = []
    ): SettlementBatch {
        return new SettlementBatch(
            $values['identifier'] ?? new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $values['account'] ?? $this->createAccount(),
            $values['start'] ?? new DateTimeImmutable('2024-01-01'),
            $values['end'] ?? new DateTimeImmutable('2024-01-15'),
            $values['status'] ?? SettlementStatus::PENDING,
            $values['grossAmount'] ?? null,
            $values['feeAmount'] ?? null,
            $values['processedAt'] ?? null,
            $values['paidAt'] ?? null,
            $values['failedAt'] ?? null,
            $values['failureReason'] ?? null,
            $values['transfer'] ?? null,
        );
    }

    private function createTransfer(
        ?SettlementBatchIdentifier $identifier = null,
        ?SettlementAccount $account = null,
        ?Money $amount = null,
        ?TransferStatus $status = null,
        ?DateTimeImmutable $sentAt = null,
    ): Transfer {
        return new Transfer(
            new TransferIdentifier(StrTestHelper::generateUuid()),
            $identifier ?? new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $account ?? $this->createAccount(),
            $amount ?? new Money(1000, Currency::JPY),
            $status ?? TransferStatus::PENDING,
            $sentAt ?? null,
        );
    }

    private function createTransferMock(
        SettlementBatchIdentifier $identifier,
        SettlementAccount $account,
        Money $amount,
        TransferStatus $status,
        ?DateTimeImmutable $sentAt
    ): Transfer {
        $mock = $this->createMock(Transfer::class);
        $mock->method('settlementBatchIdentifier')->willReturn($identifier);
        $mock->method('settlementAccount')->willReturn($account);
        $mock->method('amount')->willReturn($amount);
        $mock->method('status')->willReturn($status);
        $mock->method('sentAt')->willReturn($sentAt);

        return $mock;
    }

    private function createAccount(): SettlementAccount
    {
        return new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '9876',
            Currency::JPY,
            true
        );
    }
}
