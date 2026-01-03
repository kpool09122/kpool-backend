<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

class Payment
{
    public function __construct(
        private readonly PaymentIdentifier $paymentId,
        private readonly OrderIdentifier   $orderIdentifier,
        private readonly MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        private readonly Money             $money,
        private readonly PaymentMethod     $paymentMethod,
        private readonly DateTimeImmutable $createdAt,
        private PaymentStatus              $status,
        private ?DateTimeImmutable         $authorizedAt,
        private ?DateTimeImmutable         $capturedAt,
        private ?DateTimeImmutable         $failedAt,
        private ?string                    $failureReason,
        private Money                      $refundedMoney,
        private ?DateTimeImmutable         $lastRefundedAt,
        private ?string                    $lastRefundReason = null,
    ) {
    }

    public function paymentId(): PaymentIdentifier
    {
        return $this->paymentId;
    }

    public function orderIdentifier(): OrderIdentifier
    {
        return $this->orderIdentifier;
    }

    public function buyerMonetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->buyerMonetizationAccountIdentifier;
    }

    public function money(): Money
    {
        return $this->money;
    }

    public function paymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function authorizedAt(): ?DateTimeImmutable
    {
        return $this->authorizedAt;
    }

    public function capturedAt(): ?DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function failedAt(): ?DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function refundedMoney(): Money
    {
        return $this->refundedMoney;
    }

    public function lastRefundedAt(): ?DateTimeImmutable
    {
        return $this->lastRefundedAt;
    }

    public function lastRefundReason(): ?string
    {
        return $this->lastRefundReason;
    }

    public function authorize(DateTimeImmutable $authorizedAt): void
    {
        if ($this->status !== PaymentStatus::PENDING) {
            throw new DomainException('Payment is not pending.');
        }

        $this->authorizedAt = $authorizedAt;
        $this->status = PaymentStatus::AUTHORIZED;
    }

    public function capture(DateTimeImmutable $capturedAt): void
    {
        if ($this->status !== PaymentStatus::AUTHORIZED) {
            throw new DomainException('Payment must be authorized before capture.');
        }

        $this->capturedAt = $capturedAt;
        $this->status = PaymentStatus::CAPTURED;
    }

    public function fail(string $reason, DateTimeImmutable $failedAt): void
    {
        if (! in_array($this->status, [PaymentStatus::PENDING, PaymentStatus::AUTHORIZED], true)) {
            throw new DomainException('Only pending or authorized payment can be marked as failed.');
        }

        $this->failureReason = $reason;
        $this->failedAt = $failedAt;
        $this->status = PaymentStatus::FAILED;
    }

    public function refund(Money $refundAmount, DateTimeImmutable $refundedAt, string $reason): void
    {
        if (! in_array($this->status, [PaymentStatus::CAPTURED, PaymentStatus::PARTIALLY_REFUNDED], true)) {
            throw new DomainException('Refund is allowed only for captured payments.');
        }
        if (! $refundAmount->isSameCurrency($this->money)) {
            throw new DomainException('Refund currency must match payment currency.');
        }

        $newRefundedAmount = $this->refundedMoney->add($refundAmount);

        if ($newRefundedAmount->amount() > $this->money->amount()) {
            throw new DomainException('Refund exceeds captured amount.');
        }

        $this->refundedMoney = $newRefundedAmount;
        $this->lastRefundedAt = $refundedAt;
        $this->lastRefundReason = $reason;
        $this->status = $this->refundedMoney->amount() === $this->money->amount()
            ? PaymentStatus::REFUNDED
            : PaymentStatus::PARTIALLY_REFUNDED;
    }
}
