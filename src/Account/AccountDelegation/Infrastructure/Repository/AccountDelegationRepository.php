<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Infrastructure\Repository;

use Application\Models\Account\AccountDelegation as AccountDelegationEloquent;
use Illuminate\Database\QueryException;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\AccountDelegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

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
                throw new AccountDelegationAlreadyExistsException('An active delegation request already exists.', previous: $exception);
            }

            throw $exception;
        }
    }

    public function existsOpenByAffiliation(AffiliationIdentifier $affiliationIdentifier): bool
    {
        return AccountDelegationEloquent::query()
            ->where('affiliation_id', (string) $affiliationIdentifier)
            ->whereIn('status', [DelegationStatus::PENDING->value, DelegationStatus::APPROVED->value])
            ->exists();
    }
}
