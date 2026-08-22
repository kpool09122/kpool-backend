<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Infrastructure\Query;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\Affiliation as AffiliationModel;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInput;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInputPort;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInterface;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsOutputPort;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class ListAffiliations implements ListAffiliationsInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(ListAffiliationsInputPort $input, ListAffiliationsOutputPort $output): void
    {
        $principalAccountIdentifier = $input->principal()->accountIdentifier();
        $account = AccountModel::query()->find((string) $principalAccountIdentifier);
        if (! $account instanceof AccountModel) {
            throw new DisallowedAffiliationOperationException('Affiliation list is not allowed.');
        }

        if (! $this->canUseAffiliationApprovalOrRejectionPolicy($input, $account)) {
            throw new DisallowedAffiliationOperationException('Affiliation list is not allowed.');
        }

        $principalAccountId = (string) $principalAccountIdentifier;
        $query = AffiliationModel::query()
            ->with([
                'agencyAccount:id,name,email',
                'talentAccount:id,name,email',
            ])
            ->where(static function ($query) use ($principalAccountId): void {
                $query->where('account_affiliations.agency_account_id', $principalAccountId)
                    ->orWhere('account_affiliations.talent_account_id', $principalAccountId);
            });

        if ($input->status() !== null) {
            $query->where('account_affiliations.status', $input->status());
        }

        if ($input->viewerRole() === ListAffiliationsInput::VIEWER_ROLE_REQUESTER) {
            $query->where('account_affiliations.requested_by', $principalAccountId);
        }

        if ($input->viewerRole() === ListAffiliationsInput::VIEWER_ROLE_APPROVER) {
            $query->where('account_affiliations.requested_by', '<>', $principalAccountId);
        }

        /** @var LengthAwarePaginator<int, AffiliationModel> $paginator */
        $paginator = $query
            ->orderByDesc('account_affiliations.requested_at')
            ->orderByDesc('account_affiliations.id')
            ->paginate($input->perPage(), ['*'], 'page', $input->page());

        $affiliations = array_map(
            static fn (AffiliationModel $affiliation): AffiliationReadModel => self::toReadModel($affiliation),
            $paginator->items(),
        );

        $output->output(
            $affiliations,
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    private static function toReadModel(AffiliationModel $affiliation): AffiliationReadModel
    {
        $agencyAccount = self::relatedAccount($affiliation, 'agencyAccount');
        $talentAccount = self::relatedAccount($affiliation, 'talentAccount');

        return new AffiliationReadModel(
            affiliationIdentifier: $affiliation->id,
            agencyAccountIdentifier: $affiliation->agency_account_id,
            talentAccountIdentifier: $affiliation->talent_account_id,
            agencyAccount: self::accountSummary($agencyAccount),
            talentAccount: self::accountSummary($talentAccount),
            requestedBy: $affiliation->requested_by,
            status: $affiliation->status,
            terms: self::terms($affiliation),
            requestedAt: $affiliation->requested_at->format(DateTimeInterface::ATOM),
            activatedAt: $affiliation->activated_at?->format(DateTimeInterface::ATOM),
            terminatedAt: $affiliation->terminated_at?->format(DateTimeInterface::ATOM),
        );
    }

    private static function relatedAccount(AffiliationModel $affiliation, string $relation): AccountModel
    {
        $account = $affiliation->getRelation($relation);
        if (! $account instanceof AccountModel) {
            throw new RuntimeException("Affiliation related account '{$relation}' is missing.");
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

    /** @return array{revenueSharePercentage: int|null, contractNotes: string|null}|null */
    private static function terms(AffiliationModel $affiliation): ?array
    {
        if ($affiliation->revenue_share_percentage === null && $affiliation->contract_notes === null) {
            return null;
        }

        return [
            'revenueSharePercentage' => $affiliation->revenue_share_percentage,
            'contractNotes' => $affiliation->contract_notes,
        ];
    }

    private function canUseAffiliationApprovalOrRejectionPolicy(ListAffiliationsInputPort $input, AccountModel $account): bool
    {
        $accountType = AccountType::tryFrom((string) $account->type);
        $accountCategory = AccountCategory::tryFrom((string) $account->category);

        foreach ([AccountCategory::AGENCY, AccountCategory::TALENT] as $requestingAccountCategory) {
            $resource = Resource::account(
                $input->principal()->accountIdentifier(),
                $accountType,
                $accountCategory,
                $requestingAccountCategory,
            );

            if ($this->policyEvaluator->evaluate($input->principal(), Action::AFFILIATION_APPROVE, $resource)
                || $this->policyEvaluator->evaluate($input->principal(), Action::AFFILIATION_REJECT, $resource)) {
                return true;
            }
        }

        return false;
    }
}
