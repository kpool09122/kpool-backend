<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\MonetizationAccount as MonetizationAccountEloquent;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class MonetizationAccountRepository implements MonetizationAccountRepositoryInterface
{
    public function findById(MonetizationAccountIdentifier $identifier): ?MonetizationAccount
    {
        $eloquent = MonetizationAccountEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByAccountIdentifier(AccountIdentifier $accountIdentifier): ?MonetizationAccount
    {
        $eloquent = MonetizationAccountEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(MonetizationAccount $account): void
    {
        MonetizationAccountEloquent::query()->updateOrCreate(
            ['id' => (string) $account->monetizationAccountIdentifier()],
            [
                'account_id' => (string) $account->accountIdentifier(),
                'capabilities' => json_encode(
                    array_map(
                        static fn (Capability $c) => $c->value,
                        $account->capabilities()
                    )
                ),
                'stripe_customer_id' => $account->stripeCustomerId() !== null
                    ? (string) $account->stripeCustomerId()
                    : null,
                'stripe_connected_account_id' => $account->stripeConnectedAccountId() !== null
                    ? (string) $account->stripeConnectedAccountId()
                    : null,
            ]
        );
    }

    private function toDomainEntity(MonetizationAccountEloquent $eloquent): MonetizationAccount
    {
        $capabilities = array_map(
            static fn (string $value) => Capability::from($value),
            json_decode($eloquent->capabilities, true) ?? []
        );

        return new MonetizationAccount(
            new MonetizationAccountIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $capabilities,
            $eloquent->stripe_customer_id !== null
                ? new StripeCustomerId($eloquent->stripe_customer_id)
                : null,
            $eloquent->stripe_connected_account_id !== null
                ? new StripeConnectedAccountId($eloquent->stripe_connected_account_id)
                : null,
        );
    }
}
