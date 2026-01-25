<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Repository;

use Application\Models\Wiki\ContributionPointHistory as ContributionPointHistoryEloquent;
use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Grading\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ContributionPointHistoryRepository implements ContributionPointHistoryRepositoryInterface
{
    public function save(ContributionPointHistory $history): void
    {
        ContributionPointHistoryEloquent::query()->create([
            'id' => (string) $history->id(),
            'principal_id' => (string) $history->principalIdentifier(),
            'year_month' => (string) $history->yearMonth(),
            'points' => $history->points()->value(),
            'resource_type' => $history->resourceType()->value,
            'resource_id' => (string) $history->resourceIdentifier(),
            'contributor_type' => $history->contributorType()->value,
            'is_new_creation' => $history->isNewCreation(),
            'created_at' => $history->createdAt(),
        ]);
    }

    /**
     * @return ContributionPointHistory[]
     */
    public function findByPrincipalAndYearMonth(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
    ): array {
        $eloquents = ContributionPointHistoryEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->where('year_month', (string) $yearMonth)
            ->get();

        return $eloquents->map(fn (ContributionPointHistoryEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @return ContributionPointHistory[]
     */
    public function findByYearMonth(YearMonth $yearMonth): array
    {
        $eloquents = ContributionPointHistoryEloquent::query()
            ->where('year_month', (string) $yearMonth)
            ->get();

        return $eloquents->map(fn (ContributionPointHistoryEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function findLastPublishDate(
        PrincipalIdentifier $principalIdentifier,
        ResourceType        $resourceType,
        string              $resourceId,
        ContributorType     $contributorType,
    ): ?DateTimeImmutable {
        $eloquent = ContributionPointHistoryEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->where('resource_type', $resourceType->value)
            ->where('resource_id', $resourceId)
            ->where('contributor_type', $contributorType->value)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $eloquent->created_at?->toDateTimeImmutable();
    }

    private function toDomainEntity(ContributionPointHistoryEloquent $eloquent): ContributionPointHistory
    {
        return new ContributionPointHistory(
            new ContributionPointHistoryIdentifier($eloquent->id),
            new PrincipalIdentifier($eloquent->principal_id),
            new YearMonth($eloquent->year_month),
            new Point($eloquent->points),
            ResourceType::from($eloquent->resource_type),
            new ResourceIdentifier($eloquent->resource_id),
            ContributorType::from($eloquent->contributor_type),
            $eloquent->is_new_creation,
            $eloquent->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
        );
    }
}
