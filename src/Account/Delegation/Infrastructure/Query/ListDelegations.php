<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\Delegation as DelegationModel;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
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
        $query = DelegationModel::query()
            ->with([
                'delegateAccount:id,name,email',
                'delegatorAccount:id,name,email',
                'requestedByAccount:id,name,email',
            ])
            ->where(static function ($query) use ($accountId): void {
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

        /** @var LengthAwarePaginator<int, DelegationModel> $paginator */
        $paginator = $query->orderByDesc('account_delegations.requested_at')
            ->orderByDesc('account_delegations.id')
            ->paginate($input->perPage(), ['*'], 'page', $input->page());

        $delegations = array_map(
            static function (DelegationModel $delegation): DelegationReadModel {
                $delegateAccount = self::relatedAccount($delegation, 'delegateAccount');
                $delegatorAccount = self::relatedAccount($delegation, 'delegatorAccount');
                $requestedByAccount = self::relatedAccount($delegation, 'requestedByAccount');

                return new DelegationReadModel(
                    delegationIdentifier: $delegation->id,
                    affiliationIdentifier: $delegation->affiliation_id,
                    delegateAccountIdentifier: $delegation->delegate_account_id,
                    delegatorAccountIdentifier: $delegation->delegator_account_id,
                    requestedByAccountIdentifier: $delegation->requested_by_account_id,
                    delegateAccount: self::accountSummary($delegateAccount),
                    delegatorAccount: self::accountSummary($delegatorAccount),
                    requestedByAccount: self::accountSummary($requestedByAccount),
                    status: $delegation->status,
                    direction: $delegation->direction,
                    requestedAt: $delegation->requested_at->format(DateTimeInterface::ATOM),
                    approvedAt: $delegation->approved_at?->format(DateTimeInterface::ATOM),
                    rejectedAt: $delegation->rejected_at?->format(DateTimeInterface::ATOM),
                );
            },
            $paginator->items(),
        );

        $output->output(
            $delegations,
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private static function relatedAccount(DelegationModel $delegation, string $relation): AccountModel
    {
        $account = $delegation->getRelation($relation);
        if (! $account instanceof AccountModel) {
            throw new RuntimeException("Delegation related account '{$relation}' is missing.");
        }

        return $account;
    }

    /** @return array{accountIdentifier: string, name: string, email: string} */
    private static function accountSummary(AccountModel $account): array
    {
        return [
            'accountIdentifier' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
        ];
    }

    private function canList(ListDelegationsInputPort $input, AccountModel $account): bool
    {
        $accountType = AccountType::tryFrom((string) $account->type);
        $accountCategory = AccountCategory::tryFrom((string) $account->category);
        $canUseRequestPolicy = $input->viewerRole() !== ListDelegationsInput::VIEWER_ROLE_APPROVER;
        $canUseReviewPolicy = $input->viewerRole() !== ListDelegationsInput::VIEWER_ROLE_REQUESTER;

        $resource = Resource::account($input->principal()->accountIdentifier(), $accountType, $accountCategory);
        if ($canUseRequestPolicy && $this->policyEvaluator->evaluate($input->principal(), Action::DELEGATION_REQUEST_CREATE, $resource)) {
            return true;
        }

        if (! $canUseReviewPolicy) {
            return false;
        }

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
