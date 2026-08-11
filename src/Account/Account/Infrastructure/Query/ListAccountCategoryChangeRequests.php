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
                'account_category_change_requests.id',
                'account_category_change_requests.account_id',
                'account_category_change_requests.current_account_category',
                'account_category_change_requests.requested_account_category',
                'account_category_change_requests.status',
                'account_category_change_requests.requested_at',
                'account_category_change_requests.reviewed_by',
                'account_category_change_requests.reviewed_at',
                'account_category_change_requests.rejection_reason',
                'accounts.email as account_email',
                'accounts.type as account_type',
                'accounts.name as account_name',
                'accounts.status as account_status',
                'accounts.category as account_category',
                'accounts.phone as account_phone',
                'accounts.address_country_code as account_address_country_code',
                'accounts.address_administrative_area_code as account_address_administrative_area_code',
                'accounts.address_postal_code as account_address_postal_code',
                'accounts.address_locality as account_address_locality',
                'accounts.address_line1 as account_address_line1',
                'accounts.address_line2 as account_address_line2',
            ])
            ->join('accounts', 'accounts.id', '=', 'account_category_change_requests.account_id');

        if ($input->status() !== null) {
            $query->where('account_category_change_requests.status', $input->status());
        }

        if ($input->requestedAccountCategory() !== null) {
            $query->where('account_category_change_requests.requested_account_category', $input->requestedAccountCategory());
        }

        /** @var LengthAwarePaginator<int, AccountCategoryChangeRequestModel> $paginator */
        $paginator = $query
            ->orderByDesc('account_category_change_requests.requested_at')
            ->orderByDesc('account_category_change_requests.id')
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
                    rejectionReason: self::rejectionReason($request->rejection_reason),
                ),
                account: new AccountReadModel(
                    accountIdentifier: $request->account_id,
                    email: self::stringAttribute($request, 'account_email'),
                    type: self::stringAttribute($request, 'account_type'),
                    name: self::stringAttribute($request, 'account_name'),
                    status: self::stringAttribute($request, 'account_status'),
                    accountCategory: self::stringAttribute($request, 'account_category'),
                    phone: self::nullableStringAttribute($request, 'account_phone'),
                    address: self::address($request),
                ),
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

    /**
     * @return array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null
     */
    private static function address(AccountCategoryChangeRequestModel $request): ?array
    {
        $countryCode = self::nullableStringAttribute($request, 'account_address_country_code');
        $administrativeAreaCode = self::nullableStringAttribute($request, 'account_address_administrative_area_code');
        $postalCode = self::nullableStringAttribute($request, 'account_address_postal_code');
        $locality = self::nullableStringAttribute($request, 'account_address_locality');
        $addressLine1 = self::nullableStringAttribute($request, 'account_address_line1');
        $addressLine2 = self::nullableStringAttribute($request, 'account_address_line2');

        if (
            $countryCode === null
            && $administrativeAreaCode === null
            && $postalCode === null
            && $locality === null
            && $addressLine1 === null
            && $addressLine2 === null
        ) {
            return null;
        }

        return [
            'countryCode' => $countryCode,
            'administrativeAreaCode' => $administrativeAreaCode,
            'postalCode' => $postalCode,
            'locality' => $locality,
            'addressLine1' => $addressLine1,
            'addressLine2' => $addressLine2,
        ];
    }

    private static function stringAttribute(AccountCategoryChangeRequestModel $request, string $key): string
    {
        return (string) $request->getAttribute($key);
    }

    private static function nullableStringAttribute(AccountCategoryChangeRequestModel $request, string $key): ?string
    {
        $value = $request->getAttribute($key);

        return $value === null ? null : (string) $value;
    }
}
