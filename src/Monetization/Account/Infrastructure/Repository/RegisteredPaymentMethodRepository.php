<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\MonetizationPaymentMethod;
use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\Repository\RegisteredPaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;

class RegisteredPaymentMethodRepository implements RegisteredPaymentMethodRepositoryInterface
{
    public function findById(RegisteredPaymentMethodIdentifier $identifier): ?RegisteredPaymentMethod
    {
        $eloquent = MonetizationPaymentMethod::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByPaymentMethodId(PaymentMethodId $paymentMethodId): ?RegisteredPaymentMethod
    {
        $eloquent = MonetizationPaymentMethod::query()
            ->where('stripe_payment_method_id', (string) $paymentMethodId)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?RegisteredPaymentMethod
    {
        $eloquent = MonetizationPaymentMethod::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return RegisteredPaymentMethod[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array
    {
        $eloquents = MonetizationPaymentMethod::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->get();

        return $eloquents->map(fn (MonetizationPaymentMethod $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(RegisteredPaymentMethod $paymentMethod): void
    {
        $meta = $paymentMethod->meta();

        MonetizationPaymentMethod::query()->updateOrCreate(
            ['id' => (string) $paymentMethod->paymentMethodIdentifier()],
            [
                'monetization_account_id' => (string) $paymentMethod->monetizationAccountIdentifier(),
                'stripe_payment_method_id' => (string) $paymentMethod->paymentMethodId(),
                'type' => $paymentMethod->type()->value,
                'brand' => $meta?->brand(),
                'last4' => $meta?->last4(),
                'exp_month' => $meta?->expMonth(),
                'exp_year' => $meta?->expYear(),
                'is_default' => $paymentMethod->isDefault(),
                'status' => $paymentMethod->status()->value,
            ]
        );
    }

    private function toDomainEntity(MonetizationPaymentMethod $eloquent): RegisteredPaymentMethod
    {
        $hasMeta = $eloquent->brand !== null
            || $eloquent->last4 !== null
            || $eloquent->exp_month !== null
            || $eloquent->exp_year !== null;

        return new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($eloquent->id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            new PaymentMethodId($eloquent->stripe_payment_method_id),
            PaymentMethodType::from($eloquent->type),
            $hasMeta ? new PaymentMethodMeta(
                $eloquent->brand,
                $eloquent->last4,
                $eloquent->exp_month,
                $eloquent->exp_year,
            ) : null,
            $eloquent->is_default,
            PaymentMethodStatus::from($eloquent->status),
        );
    }
}
