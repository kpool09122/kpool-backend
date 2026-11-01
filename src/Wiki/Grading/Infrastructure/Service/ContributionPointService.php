<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Service;

use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ContributionPointService implements ContributionPointServiceInterface
{
    public function __construct(
        private ContributionPointHistoryRepositoryInterface $contributionPointHistoryRepository,
        private ContributionPointSummaryRepositoryInterface $contributionPointSummaryRepository,
        private ContributionPointHistoryFactoryInterface    $historyFactory,
        private ContributionPointSummaryFactoryInterface    $summaryFactory,
    ) {
    }

    public function grantPoints(
        ?PrincipalIdentifier $editorIdentifier,
        PrincipalIdentifier  $approverIdentifier,
        ?PrincipalIdentifier $mergerIdentifier,
        ResourceType         $resourceType,
        WikiIdentifier       $wikiIdentifier,
        bool                 $isNewCreation,
        bool                 $isTranslation,
    ): void {
        $now = new DateTimeImmutable();
        $yearMonth = YearMonth::fromDateTime($now);

        // Grant editor points with the same cooldown for original and translated articles.
        if ($editorIdentifier !== null) {
            $editorPoints = $this->calculateEditorPoints(
                $editorIdentifier,
                $resourceType,
                $wikiIdentifier,
                $isNewCreation,
                $isTranslation,
            );

            if ($editorPoints->isGreaterThenZero()) {
                $this->recordPointHistory(
                    $editorIdentifier,
                    $yearMonth,
                    $editorPoints,
                    $resourceType,
                    $wikiIdentifier,
                    ContributorType::EDITOR,
                    $isNewCreation,
                    $now,
                );

                $this->updateSummary($editorIdentifier, $yearMonth, $editorPoints);
            }
        }

        $approverPoints = new Point($isTranslation ? Point::TRANSLATION : ($isNewCreation ? Point::NEW_APPROVER : Point::UPDATE_APPROVER));

        $this->recordPointHistory(
            $approverIdentifier,
            $yearMonth,
            $approverPoints,
            $resourceType,
            $wikiIdentifier,
            ContributorType::APPROVER,
            $isNewCreation,
            $now,
        );

        $this->updateSummary($approverIdentifier, $yearMonth, $approverPoints);

        if ($mergerIdentifier !== null) {
            // Grant merger points when a merger is assigned.
            $mergerPoints = new Point($isTranslation ? Point::TRANSLATION : ($isNewCreation ? Point::NEW_MERGER : Point::UPDATE_MERGER));

            $this->recordPointHistory(
                $mergerIdentifier,
                $yearMonth,
                $mergerPoints,
                $resourceType,
                $wikiIdentifier,
                ContributorType::MERGER,
                $isNewCreation,
                $now,
            );

            $this->updateSummary($mergerIdentifier, $yearMonth, $mergerPoints);
        }
    }

    private function calculateEditorPoints(
        PrincipalIdentifier $editorIdentifier,
        ResourceType        $resourceType,
        WikiIdentifier      $wikiIdentifier,
        bool                $isNewCreation,
        bool                $isTranslation,
    ): Point {
        // Check cooldown for editor only
        $lastPublishDate = $this->contributionPointHistoryRepository->findLastPublishDate(
            $editorIdentifier,
            $resourceType,
            $wikiIdentifier,
            ContributorType::EDITOR,
        );

        if ($lastPublishDate !== null) {
            $cooldownEnd = $lastPublishDate->modify('+' . Point::COOLDOWN_DAYS . ' days');
            if ($cooldownEnd > new DateTimeImmutable()) {
                return new Point(0); // Within cooldown period
            }
        }

        return new Point($isTranslation ? Point::TRANSLATION : ($isNewCreation ? Point::NEW_EDITOR : Point::UPDATE_EDITOR));
    }

    private function recordPointHistory(
        PrincipalIdentifier $principalIdentifier,
        YearMonth           $yearMonth,
        Point               $points,
        ResourceType        $resourceType,
        WikiIdentifier      $wikiIdentifier,
        ContributorType     $roleType,
        bool                $isNewCreation,
        DateTimeImmutable   $createdAt,
    ): void {
        $history = $this->historyFactory->create(
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            $wikiIdentifier,
            $roleType,
            $isNewCreation,
            $createdAt,
        );

        $this->contributionPointHistoryRepository->save($history);
    }

    private function updateSummary(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
        Point $points,
    ): void {
        $existingSummary = $this->contributionPointSummaryRepository->findByPrincipalAndYearMonth(
            $principalIdentifier,
            $yearMonth,
        );

        if ($existingSummary !== null) {
            $existingSummary->addPoints($points);
            $existingSummary->setUpdatedAt(new DateTimeImmutable());
            $this->contributionPointSummaryRepository->save($existingSummary);
        } else {
            $newSummary = $this->summaryFactory->create($principalIdentifier, $yearMonth, $points);
            $this->contributionPointSummaryRepository->save($newSummary);
        }
    }
}
