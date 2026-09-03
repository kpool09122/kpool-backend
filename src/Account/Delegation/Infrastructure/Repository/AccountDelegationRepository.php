<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Infrastructure\Repository;

use Application\Models\Account\AccountDelegation as AccountDelegationEloquent;
use Illuminate\Database\QueryException;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Delegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class AccountDelegationRepository implements AccountDelegationRepositoryInterface
{
    public function save(AccountDelegation $delegation): void
    {
        try {
            AccountDelegationEloquent::query()->updateOrCreate(
                ['id' => (string) $delegation->delegationIdentifier()],
                [
                    'affiliation_id' => (string) $delegation->affiliationIdentifier(),
                    'delegate_account_id' => (string) $delegation->delegateAccountIdentifier(),
                    'delegator_account_id' => (string) $delegation->delegatorAccountIdentifier(),
                    'requested_by_account_id' => (string) $delegation->requestedByAccountIdentifier(),
                    'status' => $delegation->status()->value,
                    'direction' => $delegation->direction()->value,
                    'requested_at' => $delegation->requestedAt(),
                    'approved_at' => $delegation->approvedAt(),
                    'revoked_at' => $delegation->revokedAt(),
                ],
            );
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'account_delegations_open_affiliation_unique')) {
                throw new AccountDelegationAlreadyExistsException(previous: $exception);
            }

            throw $exception;
        }
    }

    public function findOpenByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?AccountDelegation
    {
        $eloquent = AccountDelegationEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->whereIn('status', [DelegationStatus::PENDING->value, DelegationStatus::APPROVED->value])
            ->first();

        return $eloquent === null ? null : $this->toDomainEntity($eloquent);
    }

    private function toDomainEntity(AccountDelegationEloquent $eloquent): AccountDelegation
    {
        return new AccountDelegation(
            new DelegationIdentifier($eloquent->id),
            new AffiliationIdentifier($eloquent->affiliation_id),
            new AccountIdentifier($eloquent->delegate_account_id),
            new AccountIdentifier($eloquent->delegator_account_id),
            new AccountIdentifier($eloquent->requested_by_account_id),
            DelegationStatus::from($eloquent->status),
            DelegationDirection::from($eloquent->direction),
            $eloquent->requested_at->toDateTimeImmutable(),
            $eloquent->approved_at?->toDateTimeImmutable(),
            $eloquent->revoked_at?->toDateTimeImmutable(),
        );
    }
}
