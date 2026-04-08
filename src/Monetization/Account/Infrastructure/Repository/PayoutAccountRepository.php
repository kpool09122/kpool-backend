<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\MonetizationPayoutAccount;
use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\Repository\PayoutAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;

class PayoutAccountRepository implements PayoutAccountRepositoryInterface
{
    public function findById(PayoutAccountIdentifier $identifier): ?PayoutAccount
    {
        $eloquent = MonetizationPayoutAccount::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByExternalAccountId(ExternalAccountId $externalAccountId): ?PayoutAccount
    {
        $eloquent = MonetizationPayoutAccount::query()
            ->where('stripe_external_account_id', (string) $externalAccountId)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findDefaultByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?PayoutAccount
    {
        $eloquent = MonetizationPayoutAccount::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->where('is_default', true)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return PayoutAccount[]
     */
    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): array
    {
        $eloquents = MonetizationPayoutAccount::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->get();

        return $eloquents->map(fn (MonetizationPayoutAccount $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(PayoutAccount $payoutAccount): void
    {
        $meta = $payoutAccount->meta();

        MonetizationPayoutAccount::query()->updateOrCreate(
            ['id' => (string) $payoutAccount->payoutAccountIdentifier()],
            [
                'monetization_account_id' => (string) $payoutAccount->monetizationAccountIdentifier(),
                'stripe_external_account_id' => (string) $payoutAccount->externalAccountId(),
                'bank_name' => $meta?->bankName(),
                'last4' => $meta?->last4(),
                'country' => $meta?->country(),
                'currency' => $meta?->currency(),
                'account_holder_type' => $meta?->accountHolderType()?->value,
                'is_default' => $payoutAccount->isDefault(),
                'status' => $payoutAccount->status()->value,
            ]
        );
    }

    private function toDomainEntity(MonetizationPayoutAccount $eloquent): PayoutAccount
    {
        $hasMeta = $eloquent->bank_name !== null
            || $eloquent->last4 !== null
            || $eloquent->country !== null
            || $eloquent->currency !== null
            || $eloquent->account_holder_type !== null;

        return new PayoutAccount(
            new PayoutAccountIdentifier($eloquent->id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            new ExternalAccountId($eloquent->stripe_external_account_id),
            $hasMeta ? new PayoutAccountMeta(
                $eloquent->bank_name,
                $eloquent->last4,
                $eloquent->country,
                $eloquent->currency,
                $eloquent->account_holder_type !== null
                    ? AccountHolderType::from($eloquent->account_holder_type)
                    : null,
            ) : null,
            $eloquent->is_default,
            PayoutAccountStatus::from($eloquent->status),
        );
    }
}
