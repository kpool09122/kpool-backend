<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInputPort;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsOutputPort;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class ListAccountCategoryChangeRequests implements ListAccountCategoryChangeRequestsInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ListAccountCategoryChangeRequestsInputPort $input, ListAccountCategoryChangeRequestsOutputPort $output): void
    {
        $reviewerAccountIdentifier = $input->principal()->accountIdentifier();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE,
            Resource::account($reviewerAccountIdentifier),
        )) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }

        $query = AccountCategoryChangeRequestModel::query()
            ->select([
                'id',
                'account_id',
                'current_account_category',
                'requested_account_category',
                'status',
                'requested_at',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ]);

        if ($input->status() !== null) {
            $query->where('status', $input->status());
        }

        if ($input->requestedAccountCategory() !== null) {
            $query->where('requested_account_category', $input->requestedAccountCategory());
        }

        /** @var LengthAwarePaginator<int, AccountCategoryChangeRequestModel> $paginator */
        $paginator = $query
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate($input->perPage(), ['*'], 'page', $input->page());

        $requests = array_map(
            static fn (AccountCategoryChangeRequestModel $request): AccountCategoryChangeRequestReadModel => new AccountCategoryChangeRequestReadModel(
                requestIdentifier: $request->id,
                accountIdentifier: $request->account_id,
                currentAccountCategory: $request->current_account_category,
                requestedAccountCategory: $request->requested_account_category,
                status: $request->status,
                requestedAt: $request->requested_at->format(DateTimeInterface::ATOM),
                reviewedBy: $request->reviewed_by,
                reviewedAt: $request->reviewed_at?->format(DateTimeInterface::ATOM),
                rejectionReason: self::rejectionReason($request->rejection_reason),
            ),
            $paginator->items(),
        );

        $output->output(
            $requests,
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    /**
     * @param array<string, string|null>|null $rejectionReason
     * @return array{code: string, detail: ?string}|null
     */
    private static function rejectionReason(?array $rejectionReason): ?array
    {
        if ($rejectionReason === null) {
            return null;
        }

        return [
            'code' => (string) $rejectionReason['code'],
            'detail' => isset($rejectionReason['detail']) ? (string) $rejectionReason['detail'] : null,
        ];
    }
}
