<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Infrastructure\Repository;

use Application\Models\Monetization\Payment as PaymentEloquent;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\Repository\PaymentRepositoryInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function findById(PaymentIdentifier $paymentIdentifier): ?Payment
    {
        $eloquent = PaymentEloquent::query()
            ->where('id', (string) $paymentIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(Payment $payment): void
    {
        PaymentEloquent::query()->updateOrCreate(
            ['id' => (string) $payment->paymentId()],
            [
                'order_id' => (string) $payment->orderIdentifier(),
                'buyer_monetization_account_id' => (string) $payment->buyerMonetizationAccountIdentifier(),
                'currency' => $payment->money()->currency()->value,
                'amount' => $payment->money()->amount(),
                'payment_method_id' => (string) $payment->paymentMethod()->paymentMethodIdentifier(),
                'payment_method_type' => $payment->paymentMethod()->type()->value,
                'payment_method_label' => $payment->paymentMethod()->label(),
                'payment_method_recurring_enabled' => $payment->paymentMethod()->isRecurringEnabled(),
                'created_at' => $payment->createdAt(),
                'status' => $payment->status()->value,
                'authorized_at' => $payment->authorizedAt(),
                'captured_at' => $payment->capturedAt(),
                'failed_at' => $payment->failedAt(),
                'failure_reason' => $payment->failureReason(),
                'refunded_amount' => $payment->refundedMoney()->amount(),
                'last_refunded_at' => $payment->lastRefundedAt(),
                'last_refund_reason' => $payment->lastRefundReason(),
            ]
        );
    }

    private function toDomainEntity(PaymentEloquent $eloquent): Payment
    {
        $currency = Currency::from($eloquent->currency);

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier($eloquent->payment_method_id),
            PaymentMethodType::from($eloquent->payment_method_type),
            $eloquent->payment_method_label,
            $eloquent->payment_method_recurring_enabled,
        );

        return new Payment(
            new PaymentIdentifier($eloquent->id),
            new OrderIdentifier($eloquent->order_id),
            new MonetizationAccountIdentifier($eloquent->buyer_monetization_account_id),
            new Money($eloquent->amount, $currency),
            $paymentMethod,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
            PaymentStatus::from($eloquent->status),
            $eloquent->authorized_at !== null
                ? new DateTimeImmutable($eloquent->authorized_at->toDateTimeString())
                : null,
            $eloquent->captured_at !== null
                ? new DateTimeImmutable($eloquent->captured_at->toDateTimeString())
                : null,
            $eloquent->failed_at !== null
                ? new DateTimeImmutable($eloquent->failed_at->toDateTimeString())
                : null,
            $eloquent->failure_reason,
            new Money($eloquent->refunded_amount, $currency),
            $eloquent->last_refunded_at !== null
                ? new DateTimeImmutable($eloquent->last_refunded_at->toDateTimeString())
                : null,
            $eloquent->last_refund_reason,
        );
    }
}
