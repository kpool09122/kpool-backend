<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestListItemReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInputPort;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsOutputPort;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class ListAccountCategoryChangeRequests implements ListAccountCategoryChangeRequestsInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
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
        $query = AccountCategoryChangeRequestModel::query();
        if ($input->status() !== null) {
            $query->where('status', $input->status());
        }
        if ($input->requestedAccountCategory() !== null) {
            $query->where('requested_account_category', $input->requestedAccountCategory());
        }
        /** @var LengthAwarePaginator<int, AccountCategoryChangeRequestModel> $paginator */
        $paginator = $query->orderByDesc('requested_at')->orderByDesc('id')
            ->paginate($input->perPage(), ['*'], 'page', $input->page());
        $requests = array_map(
            static fn (AccountCategoryChangeRequestModel $request): AccountCategoryChangeRequestListItemReadModel => new AccountCategoryChangeRequestListItemReadModel(
                request: new AccountCategoryChangeRequestReadModel(
                    requestIdentifier: $request->id,
                    accountIdentifier: $request->account_id,
                    currentAccountCategory: $request->current_account_category,
                    requestedAccountCategory: $request->requested_account_category,
                    status: $request->status,
                    requestedAt: $request->requested_at->format(DateTimeInterface::ATOM),
                    reviewedBy: $request->reviewed_by,
                    reviewedAt: $request->reviewed_at?->format(DateTimeInterface::ATOM),
                    rejectionReason: $request->rejection_reason,
                ),
                account: new AccountReadModel(
                    accountIdentifier: $request->account_id,
                    email: $request->account_email,
                    type: $request->account_type,
                    name: $request->account_name,
                    status: $request->account_status,
                    accountCategory: $request->current_account_category,
                    phone: $request->account_phone,
                    address: [
                        'countryCode' => $request->account_address_country_code,
                        'administrativeAreaCode' => $request->account_address_administrative_area_code,
                        'postalCode' => $request->account_address_postal_code,
                        'locality' => $request->account_address_locality,
                        'addressLine1' => $request->account_address_line1,
                        'addressLine2' => $request->account_address_line2,
                    ],
                ),
            ),
            $paginator->items(),
        );
        $output->output($requests, $paginator->currentPage(), $paginator->lastPage(), $paginator->total(), $paginator->perPage());
    }
}
