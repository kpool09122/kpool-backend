<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\Money;

class Transfer
{
    private TransferStatus $status;
    private ?DateTimeImmutable $sentAt;
    private ?DateTimeImmutable $failedAt;
    private ?string $failureReason;

    public function __construct(
        private readonly TransferIdentifier $transferIdentifier,
        private readonly SettlementBatchIdentifier $settlementBatchIdentifier,
        private readonly SettlementAccount $settlementAccount,
        private readonly Money $amount,
        TransferStatus $status = TransferStatus::PENDING,
        ?DateTimeImmutable $sentAt = null,
        ?DateTimeImmutable $failedAt = null,
        ?string $failureReason = null
    ) {
        $this->assertCurrency($amount);
        $this->assertStatusConsistency($status, $sentAt, $failedAt, $failureReason);
        $this->status = $status;
        $this->sentAt = $sentAt;
        $this->failedAt = $failedAt;
        $this->failureReason = $failureReason === null ? null : trim($failureReason);
    }

    public function transferIdentifier(): TransferIdentifier
    {
        return $this->transferIdentifier;
    }

    public function settlementBatchIdentifier(): SettlementBatchIdentifier
    {
        return $this->settlementBatchIdentifier;
    }

    public function settlementAccount(): SettlementAccount
    {
        return $this->settlementAccount;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): TransferStatus
    {
        return $this->status;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function markSent(DateTimeImmutable $sentAt): void
    {
        if ($this->status !== TransferStatus::PENDING) {
            throw new DomainException('Transfer is not pending.');
        }

        $this->status = TransferStatus::SENT;
        $this->sentAt = $sentAt;
    }

    public function markFailed(string $reason, DateTimeImmutable $failedAt): void
    {
        if ($this->status === TransferStatus::SENT) {
            throw new DomainException('Sent transfer cannot be marked as failed.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Failure reason must not be empty.');
        }

        $this->status = TransferStatus::FAILED;
        $this->failureReason = trim($reason);
        $this->failedAt = $failedAt;
    }

    private function assertCurrency(Money $amount): void
    {
        if ($amount->currency() !== $this->settlementAccount->currency()) {
            throw new DomainException('Transfer currency must match settlement account.');
        }
    }

    private function assertStatusConsistency(
        TransferStatus $status,
        ?DateTimeImmutable $sentAt,
        ?DateTimeImmutable $failedAt,
        ?string $failureReason
    ): void {
        $normalizedReason = $failureReason === null ? null : trim($failureReason);

        if ($status === TransferStatus::PENDING) {
            if ($sentAt !== null || $failedAt !== null || $normalizedReason !== null) {
                throw new InvalidArgumentException('Pending transfer cannot have sent/failed timestamps or failure reason.');
            }

            return;
        }

        if ($status === TransferStatus::SENT) {
            if ($sentAt === null) {
                throw new InvalidArgumentException('Sent transfer requires sentAt timestamp.');
            }
            if ($failedAt !== null || $normalizedReason !== null) {
                throw new InvalidArgumentException('Sent transfer cannot have failure details.');
            }

            return;
        }

        // TransferStatus::FAILED
        if ($failedAt === null) {
            throw new InvalidArgumentException('Failed transfer requires failedAt timestamp.');
        }
        if ($normalizedReason === null || $normalizedReason === '') {
            throw new InvalidArgumentException('Failed transfer requires failure reason.');
        }
        if ($sentAt !== null) {
            throw new InvalidArgumentException('Failed transfer cannot have sentAt timestamp.');
        }
    }
}
