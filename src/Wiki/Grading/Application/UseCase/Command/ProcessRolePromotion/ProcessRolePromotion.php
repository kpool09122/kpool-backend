<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use DateTimeImmutable;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\Event\DemotionWarningsBatchIssued;
use Source\Wiki\Grading\Domain\Facotory\DemotionWarningFactoryInterface;
use Source\Wiki\Grading\Domain\Facotory\PromotionHistoryFactoryInterface;
use Source\Wiki\Grading\Domain\Repository\ContributionPointSummaryRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchDemoted;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchPromoted;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ProcessRolePromotion implements ProcessRolePromotionInterface
{
    private const string COLLABORATOR_ROLE = 'COLLABORATOR';
    private const string SENIOR_COLLABORATOR_ROLE = 'SENIOR_COLLABORATOR';

    public function __construct(
        private ContributionPointSummaryRepositoryInterface $summaryRepository,
        private DemotionWarningRepositoryInterface $demotionWarningRepository,
        private DemotionWarningFactoryInterface $demotionWarningFactory,
        private PromotionHistoryRepositoryInterface $promotionHistoryRepository,
        private PromotionHistoryFactoryInterface $promotionHistoryFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
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
        $seniorCollaboratorRole = $this->roleRepository->findByName(self::SENIOR_COLLABORATOR_ROLE);
        $collaboratorRole = $this->roleRepository->findByName(self::COLLABORATOR_ROLE);

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
        $collaborators = $this->getPrincipalsByRole($collaboratorRole);

        // Step 4: Process demotions first
        $demoted = $this->processDemotions(
            $top10Percent,
            $warningByPrincipalId,
            $seniorCollaborators,
            $seniorCollaboratorRole,
            $collaboratorRole,
            $yearMonth,
            $now,
        );
        $output->setDemoted($demoted);

        // Step 5: Process warnings
        $warned = $this->processWarnings($top10Percent, $warningByPrincipalId, $seniorCollaborators);
        $output->setWarned($warned);

        // Step 6: Process promotions
        $promoted = $this->processPromotions(
            $top10Percent,
            $cumulativePoints,
            $collaborators,
            $seniorCollaboratorRole,
            $collaboratorRole,
        );
        $output->setPromoted($promoted);

        // Step 7: Dispatch batch events
        if (! empty($promoted)) {
            $this->eventDispatcher->dispatch(new PrincipalsBatchPromoted(
                $this->toIdentityIdentifiers($promoted),
            ));
        }

        if (! empty($demoted)) {
            $this->eventDispatcher->dispatch(new PrincipalsBatchDemoted(
                $this->toIdentityIdentifiers($demoted),
            ));
        }

        if (! empty($warned)) {
            $this->eventDispatcher->dispatch(new DemotionWarningsBatchIssued(
                $this->toIdentityIdentifiers($warned),
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
        $summaries = $this->summaryRepository->findByYearMonths($yearMonths);

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
     * @return PrincipalIdentifier[]
     */
    private function processDemotions(
        array $top10Percent,
        array $warningByPrincipalId,
        array $seniorCollaborators,
        ?Role $seniorCollaboratorRole,
        ?Role $collaboratorRole,
        YearMonth $yearMonth,
        DateTimeImmutable $now,
    ): array {
        $demoted = [];

        if ($seniorCollaboratorRole === null || $collaboratorRole === null) {
            return $demoted;
        }

        foreach ($seniorCollaborators as $principalId) {
            $isInTop10 = in_array($principalId, $top10Percent, true);
            $warning = $warningByPrincipalId[$principalId] ?? null;

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

                if ($warning->warningCount()->isExceedDemotionThreshold()) {
                    $principalIdentifier = new PrincipalIdentifier($principalId);
                    $principalGroups = $this->principalGroupRepository->findByPrincipalId($principalIdentifier);

                    foreach ($principalGroups as $group) {
                        if ($group->hasRole($seniorCollaboratorRole->roleIdentifier())) {
                            $group->removeRole($seniorCollaboratorRole->roleIdentifier());
                            $group->addRole($collaboratorRole->roleIdentifier());
                            $this->principalGroupRepository->save($group);
                        }
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

        return $demoted;
    }

    /**
     * @param string[] $top10Percent
     * @param array<string, DemotionWarning> $warningByPrincipalId
     * @param string[] $seniorCollaborators
     * @return PrincipalIdentifier[]
     */
    private function processWarnings(
        array $top10Percent,
        array $warningByPrincipalId,
        array $seniorCollaborators,
    ): array {
        $warned = [];

        foreach ($seniorCollaborators as $principalId) {
            $isInTop10 = in_array($principalId, $top10Percent, true);

            if (! $isInTop10) {
                $warning = $warningByPrincipalId[$principalId] ?? null;

                if ($warning !== null && $warning->warningCount()->isReachedWarningThreshold()) {
                    $warned[] = new PrincipalIdentifier($principalId);
                }
            }
        }

        return $warned;
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
                $principalGroups = $this->principalGroupRepository->findByPrincipalId($principalIdentifier);

                foreach ($principalGroups as $group) {
                    if ($group->hasRole($collaboratorRole->roleIdentifier())) {
                        $group->removeRole($collaboratorRole->roleIdentifier());
                        $group->addRole($seniorCollaboratorRole->roleIdentifier());
                        $this->principalGroupRepository->save($group);
                    }
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
     * @return IdentityIdentifier[]
     */
    private function toIdentityIdentifiers(array $principalIdentifiers): array
    {
        $principals = $this->principalRepository->findByIds($principalIdentifiers);

        return array_map(
            static fn ($principal) => $principal->identityIdentifier(),
            $principals
        );
    }
}
