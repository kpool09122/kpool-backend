<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class TransferTest extends TestCase
{
    /**
     * 正常系: 正しく送金インスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $transferIdentifier = new TransferIdentifier(StrTestHelper::generateUuid());
        $settlementBatchIdentifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $settlementAccount = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '1234',
            Currency::JPY,
            true
        );
        $amount = new Money(1000, Currency::JPY);
        $status = TransferStatus::SENT;
        $sentAt = new DateTimeImmutable('now');
        $transfer = $this->createTransfer(
            $transferIdentifier,
            $settlementBatchIdentifier,
            $settlementAccount,
            $amount,
            $status,
            $sentAt,
        );
        $this->assertSame($transferIdentifier, $transfer->transferIdentifier());
        $this->assertSame($settlementAccount, $transfer->settlementAccount());
        $this->assertSame($amount, $transfer->amount());
        $this->assertSame($status, $transfer->status());
        $this->assertSame($sentAt, $transfer->sentAt());
        $this->assertNull($transfer->failedAt());
        $this->assertNull($transfer->failureReason());
    }

    /**
     * 異常系: 送金通貨と清算口座の受取通貨が異なる場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenDifferentCurrency(): void
    {
        $settlementAccount = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new UserIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '1234',
            Currency::KRW,
            true
        );
        $amount = new Money(1000, Currency::JPY);
        $this->expectException(DomainException::class);
        $this->createTransfer(
            settlementAccount: $settlementAccount,
            amount: $amount,
        );
    }

    /**
     * 正常系: 送金を実行済みに更新できること.
     *
     * @return void
     */
    public function testMarkSent(): void
    {
        $transfer = $this->createTransfer();

        $this->assertSame(TransferStatus::PENDING, $transfer->status());
        $this->assertNull($transfer->sentAt());

        $sentAt = new DateTimeImmutable('2024-01-20 10:00:00');
        $transfer->markSent($sentAt);

        $this->assertSame(TransferStatus::SENT, $transfer->status());
        $this->assertSame($sentAt, $transfer->sentAt());
    }

    /**
     * 異常系: 送金済みのものを再度送金済みにできないこと.
     *
     * @return void
     */
    public function testCannotMarkSentTwice(): void
    {
        $transfer = $this->createTransfer();
        $transfer->markSent(new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $transfer->markSent(new DateTimeImmutable('+1 hour'));
    }

    /**
     * 正常系: 送金失敗を理由付きで記録できること.
     *
     * @return void
     */
    public function testMarkFailed(): void
    {
        $transfer = $this->createTransfer();

        $this->assertSame(TransferStatus::PENDING, $transfer->status());
        $this->assertNull($transfer->failureReason());
        $this->assertNull($transfer->failedAt());

        $failedAt = new DateTimeImmutable();
        $failureReason = 'bank_error';
        $transfer->markFailed($failureReason, $failedAt);

        $this->assertSame(TransferStatus::FAILED, $transfer->status());
        $this->assertSame($failureReason, $transfer->failureReason());
        $this->assertSame($failedAt, $transfer->failedAt());
    }

    /**
     * 異常系: 失敗理由が空の場合は例外となること.
     *
     * @return void
     */
    public function testEmptyFailureReason(): void
    {
        $transfer = $this->createTransfer();

        $this->expectException(InvalidArgumentException::class);

        $transfer->markFailed('', new DateTimeImmutable());
    }

    /**
     * 異常系: 送金済みの Transfer を失敗にできないこと.
     *
     * @return void
     */
    public function testCannotFailAfterSent(): void
    {
        $transfer = $this->createTransfer();
        $transfer->markSent(new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $transfer->markFailed('bank_error', new DateTimeImmutable('+1 hour'));
    }

    /**
     * 異常系: ステータスとタイムスタンプの整合性が取れていない場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructWithInconsistentState(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::SENT,
            sentAt: null,
        );
    }

    /**
     * 異常系: 送信ステータス時に失敗理由などがあると例外となること.
     *
     * @return void
     */
    public function testCannotConstructFailedWithFailedAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::SENT,
            sentAt: new DateTimeImmutable('+1 hour'),
            failedAt: new DateTimeImmutable('+1 hour'),
            failureReason: null,
        );
    }

    /**
     * 異常系: 失敗ステータス時に日時が欠けている場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructFailedWithoutTimestamp(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::FAILED,
            failedAt: null,
            failureReason: 'Sending error',
        );
    }

    /**
     * 異常系: 失敗ステータス時に理由が欠けている場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructFailedWithoutReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::FAILED,
            failedAt: new DateTimeImmutable('+1 hour'),
            failureReason: null,
        );
    }

    /**
     * 異常系: 失敗ステータス時に理由が欠けている場合は生成できないこと.
     *
     * @return void
     */
    public function testCannotConstructFailedWithSentAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::FAILED,
            sentAt: new DateTimeImmutable('+1 hour'),
            failedAt: new DateTimeImmutable('+1 hour'),
            failureReason: 'Sending error',
        );
    }

    /**
     * 異常系: Pendingステータス時にsendAtなどの不要なステータスに値があると、例外がスローされること..
     *
     * @return void
     */
    public function testCannotConstructFailedWhenPendingWithSendAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createTransfer(
            transferStatus: TransferStatus::PENDING,
            sentAt: new DateTimeImmutable('+1 hour'),
            failedAt: null,
            failureReason: null,
        );
    }

    private function createTransfer(
        ?TransferIdentifier $transferIdentifier = null,
        ?SettlementBatchIdentifier $settlementBatchIdentifier = null,
        ?SettlementAccount $settlementAccount = null,
        ?Money $amount = null,
        ?TransferStatus $transferStatus = null,
        ?DateTimeImmutable $sentAt = null,
        ?DateTimeImmutable $failedAt = null,
        ?string $failureReason = null,
    ): Transfer {
        return new Transfer(
            $transferIdentifier ?? new TransferIdentifier(StrTestHelper::generateUuid()),
            $settlementBatchIdentifier ?? new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $settlementAccount ?? new SettlementAccount(
                new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
                new UserIdentifier(StrTestHelper::generateUuid()),
                'KBank',
                '6789',
                Currency::JPY,
                true
            ),
            $amount ?? new Money(1000, Currency::JPY),
            $transferStatus ?? TransferStatus::PENDING,
            $sentAt ?? null,
            $failedAt ?? null,
            $failureReason ?? null,
        );
    }
}
