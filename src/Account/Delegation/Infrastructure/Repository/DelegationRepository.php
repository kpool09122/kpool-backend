<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Infrastructure\Repository;

use Application\Models\Account\Delegation as DelegationEloquent;
use Illuminate\Database\QueryException;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Exception\DelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class DelegationRepository implements DelegationRepositoryInterface
{
    public function save(Delegation $delegation): void
    {
        try {
            DelegationEloquent::query()->updateOrCreate(
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
                    'rejected_at' => $delegation->rejectedAt(),
                ],
            );
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'account_delegations_open_affiliation_unique')) {
                throw new DelegationAlreadyExistsException(previous: $exception);
            }

            throw $exception;
        }
    }

    public function findById(DelegationIdentifier $identifier): ?Delegation
    {
        $eloquent = DelegationEloquent::query()->find((string) $identifier);

        return $eloquent === null ? null : $this->toDomainEntity($eloquent);
    }

    public function findOpenByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?Delegation
    {
        $eloquent = DelegationEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->whereIn('status', [DelegationStatus::PENDING->value, DelegationStatus::APPROVED->value])
            ->first();

        return $eloquent === null ? null : $this->toDomainEntity($eloquent);
    }

    private function toDomainEntity(DelegationEloquent $eloquent): Delegation
    {
        return new Delegation(
            new DelegationIdentifier($eloquent->id),
            new AffiliationIdentifier($eloquent->affiliation_id),
            new AccountIdentifier($eloquent->delegate_account_id),
            new AccountIdentifier($eloquent->delegator_account_id),
            new AccountIdentifier($eloquent->requested_by_account_id),
            DelegationStatus::from($eloquent->status),
            DelegationDirection::from($eloquent->direction),
            $eloquent->requested_at->toDateTimeImmutable(),
            $eloquent->approved_at?->toDateTimeImmutable(),
            $eloquent->rejected_at?->toDateTimeImmutable(),
        );
    }
}
