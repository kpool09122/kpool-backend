<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Infrastructure\Repository;

use Application\Models\Account\Affiliation as AffiliationEloquent;
use DateTimeImmutable;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AffiliationRepository implements AffiliationRepositoryInterface
{
    public function save(Affiliation $affiliation): void
    {
        AffiliationEloquent::query()->updateOrCreate(
            ['id' => (string) $affiliation->affiliationIdentifier()],
            [
                'agency_account_id' => (string) $affiliation->agencyAccountIdentifier(),
                'talent_account_id' => (string) $affiliation->talentAccountIdentifier(),
                'requested_by' => (string) $affiliation->requestedBy(),
                'status' => $affiliation->status()->value,
                'revenue_share_percentage' => $affiliation->terms()?->revenueSharePercentage()?->value(),
                'contract_notes' => $affiliation->terms()?->contractNotes(),
                'requested_at' => $affiliation->requestedAt(),
                'activated_at' => $affiliation->activatedAt(),
                'terminated_at' => $affiliation->terminatedAt(),
            ],
        );
    }

    public function delete(Affiliation $affiliation): void
    {
        AffiliationEloquent::query()
            ->where('id', (string) $affiliation->affiliationIdentifier())
            ->delete();
    }

    public function findById(AffiliationIdentifier $identifier): ?Affiliation
    {
        $eloquent = AffiliationEloquent::query()->where('id', (string) $identifier)->first();

        return $eloquent === null ? null : $this->toDomainEntity($eloquent);
    }

    public function findActiveByTalentAccount(AccountIdentifier $talentAccountIdentifier): ?Affiliation
    {
        $eloquent = AffiliationEloquent::query()
            ->where('talent_account_id', (string) $talentAccountIdentifier)
            ->where('status', AffiliationStatus::ACTIVE->value)
            ->first();

        return $eloquent === null ? null : $this->toDomainEntity($eloquent);
    }

    /**
     * @return Affiliation[]
     */
    public function findByAgencyAccount(
        AccountIdentifier $agencyAccountIdentifier,
        ?AffiliationStatus $status = null,
    ): array {
        $query = AffiliationEloquent::query()->where('agency_account_id', (string) $agencyAccountIdentifier);
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->get()->map(fn (AffiliationEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return Affiliation[]
     */
    public function findByTalentAccount(
        AccountIdentifier $talentAccountIdentifier,
        ?AffiliationStatus $status = null,
    ): array {
        $query = AffiliationEloquent::query()->where('talent_account_id', (string) $talentAccountIdentifier);
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->get()->map(fn (AffiliationEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return Affiliation[]
     */
    public function findPendingByApprover(AccountIdentifier $approverAccountIdentifier): array
    {
        return AffiliationEloquent::query()
            ->where('status', AffiliationStatus::PENDING->value)
            ->where(static function ($query) use ($approverAccountIdentifier): void {
                $query->where(static function ($query) use ($approverAccountIdentifier): void {
                    $query->whereColumn('requested_by', 'agency_account_id')
                        ->where('talent_account_id', (string) $approverAccountIdentifier);
                })->orWhere(static function ($query) use ($approverAccountIdentifier): void {
                    $query->whereColumn('requested_by', 'talent_account_id')
                        ->where('agency_account_id', (string) $approverAccountIdentifier);
                });
            })
            ->get()
            ->map(fn (AffiliationEloquent $eloquent) => $this->toDomainEntity($eloquent))
            ->all();
    }

    public function existsActiveAffiliation(
        AccountIdentifier $agencyAccountIdentifier,
        AccountIdentifier $talentAccountIdentifier,
    ): bool {
        return AffiliationEloquent::query()
            ->where('agency_account_id', (string) $agencyAccountIdentifier)
            ->where('talent_account_id', (string) $talentAccountIdentifier)
            ->where('status', AffiliationStatus::ACTIVE->value)
            ->exists();
    }

    private function toDomainEntity(AffiliationEloquent $eloquent): Affiliation
    {
        $terms = $eloquent->revenue_share_percentage === null && $eloquent->contract_notes === null
            ? null
            : new AffiliationTerms(
                $eloquent->revenue_share_percentage === null ? null : new Percentage($eloquent->revenue_share_percentage),
                $eloquent->contract_notes,
            );

        return new Affiliation(
            new AffiliationIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->agency_account_id),
            new AccountIdentifier($eloquent->talent_account_id),
            new AccountIdentifier($eloquent->requested_by),
            AffiliationStatus::from($eloquent->status),
            $terms,
            new DateTimeImmutable($eloquent->requested_at->toDateTimeString()),
            $eloquent->activated_at === null ? null : new DateTimeImmutable($eloquent->activated_at->toDateTimeString()),
            $eloquent->terminated_at === null ? null : new DateTimeImmutable($eloquent->terminated_at->toDateTimeString()),
        );
    }
}
