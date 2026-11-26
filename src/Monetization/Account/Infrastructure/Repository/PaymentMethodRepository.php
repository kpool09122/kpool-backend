<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\PaymentMethod as PaymentMethodEloquent;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function findById(PaymentMethodIdentifier $identifier): ?PaymentMethod
    {
        $eloquent = PaymentMethodEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByExternalPaymentMethodId(ExternalPaymentMethodId $externalPaymentMethodId, MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PaymentMethod
    {
        $eloquent = PaymentMethodEloquent::query()
            ->where('external_payment_method_id', (string) $externalPaymentMethodId)
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PaymentMethod
    {
        $eloquent = PaymentMethodEloquent::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return PaymentMethod[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array
    {
        $eloquents = PaymentMethodEloquent::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->orderBy('id')
            ->get();

        return $eloquents->map(fn (PaymentMethodEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(PaymentMethod $paymentMethod): void
    {
        $meta = $paymentMethod->meta();

        PaymentMethodEloquent::query()->updateOrCreate(
            ['id' => (string) $paymentMethod->paymentMethodIdentifier()],
            [
                'monetization_account_id' => (string) $paymentMethod->monetizationAccountIdentifier(),
                'external_payment_method_id' => (string) $paymentMethod->externalPaymentMethodId(),
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

    private function toDomainEntity(PaymentMethodEloquent $eloquent): PaymentMethod
    {
        $hasMeta = $eloquent->brand !== null
            || $eloquent->last4 !== null
            || $eloquent->exp_month !== null
            || $eloquent->exp_year !== null;

        return new PaymentMethod(
            new PaymentMethodIdentifier($eloquent->id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            new ExternalPaymentMethodId($eloquent->external_payment_method_id),
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
