<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\UpdateContributionPointSummary;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class UpdateContributionPointSummary implements UpdateContributionPointSummaryInterface
{
    public function __construct(
        private ContributionPointHistoryRepositoryInterface $contributionPointHistoryRepository,
        private ContributionPointSummaryRepositoryInterface $contributionPointSummaryRepository,
        private ContributionPointSummaryFactoryInterface $summaryFactory,
    ) {
    }

    public function process(
        UpdateContributionPointSummaryInputPort $input,
        UpdateContributionPointSummaryOutputPort $output,
    ): void {
        $yearMonth = $input->yearMonth();
        $histories = $this->contributionPointHistoryRepository->findByYearMonth($yearMonth);

        $pointsByPrincipal = [];
        foreach ($histories as $history) {
            $principalId = (string) $history->principalIdentifier();
            $pointsByPrincipal[$principalId] = ($pointsByPrincipal[$principalId] ?? new Point(0))->add($history->points());
        }

        $existingSummaries = $this->contributionPointSummaryRepository->findByYearMonth($yearMonth);
        $summaryByPrincipalId = [];
        foreach ($existingSummaries as $summary) {
            $summaryByPrincipalId[(string) $summary->principalIdentifier()] = $summary;
        }

        foreach ($pointsByPrincipal as $principalId => $points) {
            $existingSummary = $summaryByPrincipalId[$principalId] ?? null;

            if ($existingSummary !== null) {
                $existingSummary->setPoints($points);
                $existingSummary->setUpdatedAt(new DateTimeImmutable());
                $this->contributionPointSummaryRepository->save($existingSummary);
            } else {
                $newSummary = $this->summaryFactory->create(
                    new PrincipalIdentifier($principalId),
                    $yearMonth,
                    $points,
                );
                $this->contributionPointSummaryRepository->save($newSummary);
            }
        }

        $output->setUpdatedCount(count($pointsByPrincipal));
    }
}
