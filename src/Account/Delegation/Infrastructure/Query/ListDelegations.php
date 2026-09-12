<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\AccountDelegation as AccountDelegationModel;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Query\DelegationReadModel;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInput;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInputPort;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInterface;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsOutputPort;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class ListDelegations implements ListDelegationsInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(ListDelegationsInputPort $input, ListDelegationsOutputPort $output): void
    {
        $accountIdentifier = $input->principal()->accountIdentifier();
        $account = AccountModel::query()->find((string) $accountIdentifier);
        if (! $account instanceof AccountModel || ! $this->canList($input, $account)) {
            throw new DisallowedDelegationOperationException('Delegation list is not allowed.');
        }

        $accountId = (string) $accountIdentifier;
        $query = AccountDelegationModel::query()->where(static function ($query) use ($accountId): void {
            $query->where('account_delegations.delegate_account_id', $accountId)
                ->orWhere('account_delegations.delegator_account_id', $accountId);
        });
        if ($input->status() !== null) {
            $query->where('account_delegations.status', $input->status());
        }
        if ($input->viewerRole() === ListDelegationsInput::VIEWER_ROLE_REQUESTER) {
            $query->where('account_delegations.requested_by_account_id', $accountId);
        }
        if ($input->viewerRole() === ListDelegationsInput::VIEWER_ROLE_APPROVER) {
            $query->where('account_delegations.requested_by_account_id', '<>', $accountId);
        }

        /** @var LengthAwarePaginator<int, AccountDelegationModel> $paginator */
        $paginator = $query->orderByDesc('account_delegations.requested_at')
            ->orderByDesc('account_delegations.id')
            ->paginate($input->perPage(), ['*'], 'page', $input->page());

        $output->output(
            array_map(static fn (AccountDelegationModel $delegation): DelegationReadModel => self::toReadModel($delegation), $paginator->items()),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private static function toReadModel(AccountDelegationModel $delegation): DelegationReadModel
    {
        return new DelegationReadModel(
            delegationIdentifier: $delegation->id,
            affiliationIdentifier: $delegation->affiliation_id,
            delegateAccountIdentifier: $delegation->delegate_account_id,
            delegatorAccountIdentifier: $delegation->delegator_account_id,
            requestedByAccountIdentifier: $delegation->requested_by_account_id,
            status: $delegation->status,
            direction: $delegation->direction,
            requestedAt: $delegation->requested_at->format(DateTimeInterface::ATOM),
            approvedAt: $delegation->approved_at?->format(DateTimeInterface::ATOM),
            revokedAt: $delegation->revoked_at?->format(DateTimeInterface::ATOM),
        );
    }

    private function canList(ListDelegationsInputPort $input, AccountModel $account): bool
    {
        $accountType = AccountType::tryFrom((string) $account->type);
        $accountCategory = AccountCategory::tryFrom((string) $account->category);
        foreach ([AccountCategory::AGENCY, AccountCategory::TALENT] as $requestingAccountCategory) {
            $resource = Resource::account($input->principal()->accountIdentifier(), $accountType, $accountCategory, $requestingAccountCategory);
            if ($this->policyEvaluator->evaluate($input->principal(), Action::DELEGATION_APPROVE, $resource)
                || $this->policyEvaluator->evaluate($input->principal(), Action::DELEGATION_REJECT, $resource)) {
                return true;
            }
        }

        return false;
    }
}
