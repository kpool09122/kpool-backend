<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class SettlementBatch
{
    private Money $grossAmount;
    private Money $feeAmount;
    private Money $netAmount;
    private SettlementStatus $status;
    private ?DateTimeImmutable $processedAt;
    private ?DateTimeImmutable $paidAt;
    private ?DateTimeImmutable $failedAt;
    private ?string $failureReason;

    public function __construct(
        private readonly SettlementBatchIdentifier $settlementBatchIdentifier,
        private readonly MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private readonly Currency $currency,
        private readonly DateTimeImmutable $periodStart,
        private readonly DateTimeImmutable $periodEnd,
        SettlementStatus $status = SettlementStatus::PENDING,
        ?Money $grossAmount = null,
        ?Money $feeAmount = null,
        ?DateTimeImmutable $processedAt = null,
        ?DateTimeImmutable $paidAt = null,
        ?DateTimeImmutable $failedAt = null,
        ?string $failureReason = null,
    ) {
        $this->assertPeriod($periodStart, $periodEnd);
        $this->grossAmount = $grossAmount ?? new Money(0, $this->currency);
        $this->feeAmount = $feeAmount ?? new Money(0, $this->currency);
        $this->assertCurrency($this->grossAmount);
        $this->assertCurrency($this->feeAmount);
        $this->assertFeeNotOverGross($this->feeAmount, $this->grossAmount);
        $this->netAmount = $this->grossAmount->subtract($this->feeAmount);
        $this->assertStatusConsistency($status, $processedAt, $paidAt, $failedAt, $failureReason);

        $this->status = $status;
        $this->processedAt = $processedAt;
        $this->paidAt = $paidAt;
        $this->failedAt = $failedAt;
        $this->failureReason = $failureReason === null ? null : trim($failureReason);
    }

    public function settlementBatchIdentifier(): SettlementBatchIdentifier
    {
        return $this->settlementBatchIdentifier;
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function periodStart(): DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function periodEnd(): DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function grossAmount(): Money
    {
        return $this->grossAmount;
    }

    public function feeAmount(): Money
    {
        return $this->feeAmount;
    }

    public function netAmount(): Money
    {
        return $this->netAmount;
    }

    public function status(): SettlementStatus
    {
        return $this->status;
    }

    public function processedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function recordRevenue(Money $revenue): void
    {
        $this->assertPending();
        $this->assertCurrency($revenue);

        $this->grossAmount = $this->grossAmount->add($revenue);
        $this->recalculateNet();
    }

    public function applyFee(Money $fee): void
    {
        $this->assertPending();
        $this->assertCurrency($fee);
        $this->assertFeeNotOverGross($fee, $this->grossAmount);

        $this->feeAmount = $fee;
        $this->recalculateNet();
    }

    public function markProcessing(DateTimeImmutable $processedAt): void
    {
        $this->assertPending();
        $this->processedAt = $processedAt;
        $this->status = SettlementStatus::PROCESSING;
    }

    public function markPaid(DateTimeImmutable $paidAt): void
    {
        if ($this->status !== SettlementStatus::PROCESSING) {
            throw new DomainException('Batch must be processing before marking as paid.');
        }

        $this->paidAt = $paidAt;
        $this->status = SettlementStatus::PAID;
    }

    public function fail(string $reason, DateTimeImmutable $failedAt): void
    {
        if ($this->status === SettlementStatus::PAID) {
            throw new DomainException('Paid batch cannot be marked as failed.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Failure reason must not be empty.');
        }

        $this->failureReason = trim($reason);
        $this->failedAt = $failedAt;
        $this->status = SettlementStatus::FAILED;
    }

    private function recalculateNet(): void
    {
        $this->netAmount = $this->grossAmount->subtract($this->feeAmount);
    }

    private function assertPending(): void
    {
        if ($this->status !== SettlementStatus::PENDING) {
            throw new DomainException('Batch is not pending.');
        }
    }

    private function assertCurrency(Money $money): void
    {
        if ($money->currency() !== $this->currency) {
            throw new DomainException('Currency mismatch for settlement batch.');
        }
    }

    private function assertPeriod(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($end < $start) {
            throw new DomainException('Settlement period end must not be before start.');
        }
    }

    private function assertFeeNotOverGross(Money $fee, Money $gross): void
    {
        if ($fee->amount() > $gross->amount()) {
            throw new DomainException('Fee cannot exceed gross amount.');
        }
    }

    private function assertStatusConsistency(
        SettlementStatus $status,
        ?DateTimeImmutable $processedAt,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $failedAt,
        ?string $failureReason
    ): void {
        $normalizedReason = $failureReason === null ? null : trim($failureReason);

        if ($status === SettlementStatus::PENDING) {
            if ($processedAt !== null || $paidAt !== null || $failedAt !== null || $normalizedReason !== null) {
                throw new InvalidArgumentException('Pending batch cannot have timestamps or failure reason.');
            }

            return;
        }

        if ($status === SettlementStatus::PROCESSING) {
            if ($processedAt === null) {
                throw new InvalidArgumentException('Processing batch requires processedAt timestamp.');
            }
            if ($paidAt !== null || $failedAt !== null || $normalizedReason !== null) {
                throw new InvalidArgumentException('Processing batch cannot be paid or failed.');
            }

            return;
        }

        if ($status === SettlementStatus::PAID) {
            if ($processedAt === null || $paidAt === null) {
                throw new InvalidArgumentException('Paid batch requires processedAt and paidAt timestamps.');
            }
            if ($failedAt !== null || $normalizedReason !== null) {
                throw new InvalidArgumentException('Paid batch cannot have failure details.');
            }

            return;
        }

        // SettlementStatus::FAILED
        if ($failedAt === null) {
            throw new InvalidArgumentException('Failed batch requires failedAt timestamp.');
        }
        if ($normalizedReason === null || $normalizedReason === '') {
            throw new InvalidArgumentException('Failed batch requires failure reason.');
        }
        if ($paidAt !== null) {
            throw new InvalidArgumentException('Failed batch cannot have paidAt timestamp.');
        }
    }
}
