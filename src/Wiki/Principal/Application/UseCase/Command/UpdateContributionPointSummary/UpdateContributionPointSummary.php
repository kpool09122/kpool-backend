<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdateContributionPointSummary;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Factory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class UpdateContributionPointSummary implements UpdateContributionPointSummaryInterface
{
    public function __construct(
        private ContributionPointHistoryRepositoryInterface $historyRepository,
        private ContributionPointSummaryRepositoryInterface $summaryRepository,
        private ContributionPointSummaryFactoryInterface $summaryFactory,
    ) {
    }

    public function process(
        UpdateContributionPointSummaryInputPort $input,
        UpdateContributionPointSummaryOutputPort $output,
    ): void {
        $yearMonth = $input->yearMonth();
        $histories = $this->historyRepository->findByYearMonth($yearMonth);

        $pointsByPrincipal = [];
        foreach ($histories as $history) {
            $principalId = (string) $history->principalIdentifier();
            $pointsByPrincipal[$principalId] = ($pointsByPrincipal[$principalId] ?? new Point(0))->add($history->points());
        }

        $existingSummaries = $this->summaryRepository->findByYearMonth($yearMonth);
        $summaryByPrincipalId = [];
        foreach ($existingSummaries as $summary) {
            $summaryByPrincipalId[(string) $summary->principalIdentifier()] = $summary;
        }

        foreach ($pointsByPrincipal as $principalId => $points) {
            $existingSummary = $summaryByPrincipalId[$principalId] ?? null;

            if ($existingSummary !== null) {
                $existingSummary->setPoints($points);
                $existingSummary->setUpdatedAt(new DateTimeImmutable());
                $this->summaryRepository->save($existingSummary);
            } else {
                $newSummary = $this->summaryFactory->create(
                    new PrincipalIdentifier($principalId),
                    $yearMonth,
                    $points,
                );
                $this->summaryRepository->save($newSummary);
            }
        }

        $output->setUpdatedCount(count($pointsByPrincipal));
    }
}
