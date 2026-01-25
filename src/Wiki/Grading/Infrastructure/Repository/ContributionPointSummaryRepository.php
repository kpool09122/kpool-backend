<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Repository;

use Application\Models\Wiki\ContributionPointSummary as ContributionPointSummaryEloquent;
use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class ContributionPointSummaryRepository implements ContributionPointSummaryRepositoryInterface
{
    public function save(ContributionPointSummary $summary): void
    {
        ContributionPointSummaryEloquent::query()->updateOrCreate(
            ['id' => (string) $summary->id()],
            [
                'principal_id' => (string) $summary->principalIdentifier(),
                'year_month' => (string) $summary->yearMonth(),
                'points' => $summary->points()->value(),
            ]
        );
    }

    public function findByPrincipalAndYearMonth(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
    ): ?ContributionPointSummary {
        $eloquent = ContributionPointSummaryEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->where('year_month', (string) $yearMonth)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return ContributionPointSummary[]
     */
    public function findByYearMonth(YearMonth $yearMonth): array
    {
        $eloquents = ContributionPointSummaryEloquent::query()
            ->where('year_month', (string) $yearMonth)
            ->get();

        return $eloquents->map(fn (ContributionPointSummaryEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    /**
     * @param YearMonth[] $yearMonths
     * @return ContributionPointSummary[]
     */
    public function findByYearMonths(array $yearMonths): array
    {
        $yearMonthStrings = array_map(static fn (YearMonth $ym) => (string) $ym, $yearMonths);

        $eloquents = ContributionPointSummaryEloquent::query()
            ->whereIn('year_month', $yearMonthStrings)
            ->get();

        return $eloquents->map(fn (ContributionPointSummaryEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    private function toDomainEntity(ContributionPointSummaryEloquent $eloquent): ContributionPointSummary
    {
        return new ContributionPointSummary(
            new ContributionPointSummaryIdentifier($eloquent->id),
            new PrincipalIdentifier($eloquent->principal_id),
            new YearMonth($eloquent->year_month),
            new Point($eloquent->points),
            $eloquent->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
            $eloquent->updated_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
        );
    }
}
