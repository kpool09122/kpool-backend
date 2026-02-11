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
        private ContributionPointHistoryRepositoryInterface $historyRepository,
        private ContributionPointSummaryRepositoryInterface $summaryRepository,
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
    ): void {
        $now = new DateTimeImmutable();
        $yearMonth = YearMonth::fromDateTime($now);

        // Grant editor points (if not a translation article)
        if ($editorIdentifier !== null) {
            $editorPoints = $this->calculateEditorPoints(
                $editorIdentifier,
                $resourceType,
                $wikiIdentifier,
                $isNewCreation,
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

        // Grant approver points (if not a translation article)
        if ($editorIdentifier !== null) {
            $approverPoints = $isNewCreation ? new Point(Point::NEW_APPROVER) : new Point(Point::UPDATE_APPROVER);

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
        }


        if ($mergerIdentifier !== null) {
            // Grant merger points (always, including translation articles)
            $mergerPoints = $isNewCreation ? new Point(Point::NEW_MERGER) : new Point(Point::UPDATE_MERGER);

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
    ): Point {
        // Check cooldown for editor only
        $lastPublishDate = $this->historyRepository->findLastPublishDate(
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

        return $isNewCreation ? new Point(Point::NEW_EDITOR) : new Point(Point::UPDATE_EDITOR);
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

        $this->historyRepository->save($history);
    }

    private function updateSummary(
        PrincipalIdentifier $principalIdentifier,
        YearMonth $yearMonth,
        Point $points,
    ): void {
        $existingSummary = $this->summaryRepository->findByPrincipalAndYearMonth(
            $principalIdentifier,
            $yearMonth,
        );

        if ($existingSummary !== null) {
            $existingSummary->addPoints($points);
            $existingSummary->setUpdatedAt(new DateTimeImmutable());
            $this->summaryRepository->save($existingSummary);
        } else {
            $newSummary = $this->summaryFactory->create($principalIdentifier, $yearMonth, $points);
            $this->summaryRepository->save($newSummary);
        }
    }
}
