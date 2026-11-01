<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use DateTimeImmutable;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Grading\Application\Service\CollaboratorRoleTransitionServiceInterface;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\Event\DemotionWarningsBatchIssued;
use Source\Wiki\Grading\Domain\Facotory\DemotionWarningFactoryInterface;
use Source\Wiki\Grading\Domain\Facotory\PromotionHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchDemoted;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchPromoted;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ProcessRolePromotion implements ProcessRolePromotionInterface
{
    private const string COLLABORATOR_ROLE = 'Collaborator';
    private const string SENIOR_COLLABORATOR_ROLE = 'SeniorCollaborator';

    public function __construct(
        private ContributionPointSummaryRepositoryInterface $contributionPointSummaryRepository,
        private DemotionWarningRepositoryInterface $demotionWarningRepository,
        private DemotionWarningFactoryInterface $demotionWarningFactory,
        private PromotionHistoryRepositoryInterface $promotionHistoryRepository,
        private PromotionHistoryFactoryInterface $promotionHistoryFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private CollaboratorRoleTransitionServiceInterface $collaboratorRoleTransitionService,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(
        ProcessRolePromotionInputPort $input,
        ProcessRolePromotionOutputPort $output,
    ): void {
        $yearMonth = $input->yearMonth();
        $now = new DateTimeImmutable();

        // Load roles once
        $seniorCollaboratorRole = $this->roleRepository->findSystemByName(self::SENIOR_COLLABORATOR_ROLE);
        $collaboratorRole = $this->roleRepository->findSystemByName(self::COLLABORATOR_ROLE);

        // Step 1: Calculate cumulative points for last 3 months
        $yearMonths = $this->getEvaluationPeriodMonths($yearMonth);
        $cumulativePoints = $this->calculateCumulativePoints($yearMonths);

        // Step 2: Filter eligible principals (50pt+ threshold)
        $eligiblePrincipalPoints = array_filter(
            $cumulativePoints,
            static fn (int $points): bool => $points >= Point::PROMOTION_THRESHOLD
        );

        // Step 3: Calculate top 10% (minimum 10 people)
        $top10Percent = $this->calculateTop10Percent($eligiblePrincipalPoints);

        // Load all warnings once for demotion and warning processing
        $allWarnings = $this->demotionWarningRepository->findAll();
        $warningByPrincipalId = [];
        foreach ($allWarnings as $warning) {
            $warningByPrincipalId[(string) $warning->principalIdentifier()] = $warning;
        }

        // Load collaborators once
        $seniorCollaborators = $this->getPrincipalsByRole($seniorCollaboratorRole);
        $collaborators = array_values(array_diff($this->getPrincipalsByRole($collaboratorRole), $seniorCollaborators));

        // Step 4: Evaluate senior collaborators before promotions
        [$demoted, $warned] = $this->evaluateSeniorCollaborators(
            $top10Percent,
            $warningByPrincipalId,
            $seniorCollaborators,
            $seniorCollaboratorRole,
            $collaboratorRole,
            $yearMonth,
            $now,
        );
        $output->setDemoted($demoted);
        $output->setWarned($warned);

        // Step 5: Process promotions
        $promoted = $this->processPromotions(
            $top10Percent,
            $cumulativePoints,
            $collaborators,
            $seniorCollaboratorRole,
            $collaboratorRole,
        );
        $output->setPromoted($promoted);

        // Step 6: Dispatch batch events
        if (! empty($promoted)) {
            $this->eventDispatcher->dispatch(new PrincipalsBatchPromoted(
                $this->toAccountIdentifiers($promoted),
            ));
        }

        if (! empty($demoted)) {
            $this->eventDispatcher->dispatch(new PrincipalsBatchDemoted(
                $this->toAccountIdentifiers($demoted),
            ));
        }

        if (! empty($warned)) {
            $this->eventDispatcher->dispatch(new DemotionWarningsBatchIssued(
                $this->toAccountIdentifiers($warned),
            ));
        }
    }

    /**
     * @return YearMonth[]
     */
    private function getEvaluationPeriodMonths(YearMonth $currentMonth): array
    {
        $months = [];
        for ($i = 0; $i < Point::EVALUATION_MONTHS; $i++) {
            $months[] = $currentMonth->subtract($i);
        }

        return $months;
    }

    /**
     * @param YearMonth[] $yearMonths
     * @return array<string, int>
     */
    private function calculateCumulativePoints(array $yearMonths): array
    {
        $summaries = $this->contributionPointSummaryRepository->findByYearMonths($yearMonths);

        $cumulativePoints = [];
        foreach ($summaries as $summary) {
            $principalId = (string) $summary->principalIdentifier();
            $cumulativePoints[$principalId] = ($cumulativePoints[$principalId] ?? 0) + $summary->points()->value();
        }

        return $cumulativePoints;
    }

    /**
     * @param array<string, int> $eligiblePrincipalPoints
     * @return string[]
     */
    private function calculateTop10Percent(array $eligiblePrincipalPoints): array
    {
        // Sort by points descending
        arsort($eligiblePrincipalPoints);

        $totalCount = count($eligiblePrincipalPoints);
        $top10PercentCount = (int) ceil($totalCount * Point::TOP_PERCENTAGE);

        // Ensure minimum 10 people
        $targetCount = max($top10PercentCount, Point::MINIMUM_PROMOTED_COUNT);

        // But don't exceed actual eligible count
        $targetCount = min($targetCount, $totalCount);

        return array_slice(array_keys($eligiblePrincipalPoints), 0, $targetCount);
    }

    /**
     * @param string[] $top10Percent
     * @param array<string, DemotionWarning> $warningByPrincipalId
     * @param string[] $seniorCollaborators
     * @return array{PrincipalIdentifier[], PrincipalIdentifier[]}
     */
    private function evaluateSeniorCollaborators(
        array $top10Percent,
        array $warningByPrincipalId,
        array $seniorCollaborators,
        ?Role $seniorCollaboratorRole,
        ?Role $collaboratorRole,
        YearMonth $yearMonth,
        DateTimeImmutable $now,
    ): array {
        $demoted = [];
        $warned = [];

        if ($seniorCollaboratorRole === null || $collaboratorRole === null) {
            return [$demoted, $warned];
        }

        foreach ($seniorCollaborators as $principalId) {
            $isInTop10 = in_array($principalId, $top10Percent, true);
            $warning = $warningByPrincipalId[$principalId] ?? null;

            // Do not evaluate the same month twice or move an evaluation backwards.
            if ($warning !== null && (string) $warning->lastWarningMonth() >= (string) $yearMonth) {
                continue;
            }

            if ($isInTop10) {
                if ($warning !== null) {
                    $warning->resetWarningCount();
                    $warning->setLastWarningMonth($yearMonth);
                    $warning->setUpdatedAt($now);
                    $this->demotionWarningRepository->save($warning);
                }
            } else {
                if ($warning === null) {
                    $warning = $this->demotionWarningFactory->create(
                        new PrincipalIdentifier($principalId),
                        $yearMonth,
                    );
                } else {
                    $warning->incrementWarningCount();
                    $warning->setLastWarningMonth($yearMonth);
                    $warning->setUpdatedAt($now);
                }

                $this->demotionWarningRepository->save($warning);

                if ($warning->warningCount()->isReachedWarningThreshold()) {
                    $warned[] = new PrincipalIdentifier($principalId);
                }

                if ($warning->warningCount()->isExceedDemotionThreshold()) {
                    $principalIdentifier = new PrincipalIdentifier($principalId);
                    if (! $this->collaboratorRoleTransitionService->demote($principalIdentifier, $seniorCollaboratorRole)) {
                        continue;
                    }

                    $history = $this->promotionHistoryFactory->create(
                        $principalIdentifier,
                        self::SENIOR_COLLABORATOR_ROLE,
                        self::COLLABORATOR_ROLE,
                        'Demoted due to 3 consecutive months outside top 10%',
                    );
                    $this->promotionHistoryRepository->save($history);

                    $this->demotionWarningRepository->delete($warning);

                    $demoted[] = $principalIdentifier;
                }
            }
        }

        return [$demoted, $warned];
    }

    /**
     * @param string[] $top10Percent
     * @param array<string, int> $cumulativePoints
     * @param string[] $collaborators
     * @return PrincipalIdentifier[]
     */
    private function processPromotions(
        array $top10Percent,
        array $cumulativePoints,
        array $collaborators,
        ?Role $seniorCollaboratorRole,
        ?Role $collaboratorRole,
    ): array {
        $promoted = [];

        if ($seniorCollaboratorRole === null || $collaboratorRole === null) {
            return $promoted;
        }

        foreach ($top10Percent as $principalId) {
            if (in_array($principalId, $collaborators, true)) {
                $principalIdentifier = new PrincipalIdentifier($principalId);

                try {
                    if (! $this->collaboratorRoleTransitionService->promote($principalIdentifier, $seniorCollaboratorRole)) {
                        continue;
                    }
                } catch (PrincipalGroupNotFoundException) {
                    continue;
                }

                $points = $cumulativePoints[$principalId] ?? 0;
                $history = $this->promotionHistoryFactory->create(
                    $principalIdentifier,
                    self::COLLABORATOR_ROLE,
                    self::SENIOR_COLLABORATOR_ROLE,
                    'Promoted for being in top 10% with ' . $points . ' points',
                );
                $this->promotionHistoryRepository->save($history);

                $promoted[] = $principalIdentifier;
            }
        }

        return $promoted;
    }

    /**
     * @return string[]
     */
    private function getPrincipalsByRole(?Role $role): array
    {
        if ($role === null) {
            return [];
        }

        $principalGroups = $this->principalGroupRepository->findByRole($role->roleIdentifier());

        $principalIds = [];
        foreach ($principalGroups as $group) {
            foreach (array_keys($group->members()) as $memberId) {
                $principalIds[] = $memberId;
            }
        }

        return array_unique($principalIds);
    }

    /**
     * @param PrincipalIdentifier[] $principalIdentifiers
     * @return AccountIdentifier[]
     */
    private function toAccountIdentifiers(array $principalIdentifiers): array
    {
        $principals = $this->principalRepository->findByIds(array_values($principalIdentifiers));

        $accounts = [];
        foreach ($principals as $principal) {
            $accounts[(string) $principal->accountIdentifier()] = $principal->accountIdentifier();
        }

        return array_values($accounts);
    }
}
