<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class RollbackAgency implements RollbackAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private AgencySnapshotRepositoryInterface $snapshotRepository,
        private AgencySnapshotFactoryInterface $snapshotFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AgencyHistoryFactoryInterface $agencyHistoryFactory,
        private AgencyHistoryRepositoryInterface $agencyHistoryRepository,
    ) {
    }

    /**
     * @param RollbackAgencyInputPort $input
     * @return Agency[]
     * @throws AgencyNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackAgencyInputPort $input): array
    {
        // 1. Agency存在確認
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());
        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        // 2. Principal存在確認と権限チェック
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::ROLLBACK, $resourceIdentifier)) {
            throw new DisallowedException();
        }

        // 3. targetVersionが現在のバージョンより前であることを確認
        $targetVersion = $input->targetVersion();
        if (! $agency->isVersionGreaterThan($targetVersion)) {
            throw new InvalidRollbackTargetVersionException();
        }

        // 4. 翻訳セット内の全Agencyを取得
        $allAgencies = $this->agencyRepository->findByTranslationSetIdentifier(
            $agency->translationSetIdentifier()
        );

        // 5. バージョン一致チェック（Entityメソッド使用）
        $baseVersion = $agency->version();
        foreach ($allAgencies as $a) {
            if (! $a->hasSameVersion($baseVersion)) {
                throw new VersionMismatchException();
            }
        }

        // 6. 翻訳セット内の全Snapshotを一括取得（N+1解消）
        $snapshots = $this->snapshotRepository->findByTranslationSetIdentifierAndVersion(
            $agency->translationSetIdentifier(),
            $targetVersion
        );

        // AgencyIdentifierをキーにしたマップを作成
        $snapshotMap = [];
        foreach ($snapshots as $snapshot) {
            $snapshotMap[(string) $snapshot->agencyIdentifier()] = $snapshot;
        }

        // 7. 各Agencyをロールバック
        $rolledBackAgencies = [];
        foreach ($allAgencies as $a) {
            $snapshot = $snapshotMap[(string) $a->agencyIdentifier()] ?? null;
            if ($snapshot === null) {
                throw new SnapshotNotFoundException();
            }

            // Agencyを復元（バージョンは新規インクリメント）
            $a->setName($snapshot->name());
            $a->setNormalizedName($snapshot->normalizedName());
            $a->setCEO($snapshot->CEO());
            $a->setNormalizedCEO($snapshot->normalizedCEO());
            $a->setFoundedIn($snapshot->foundedIn());
            $a->setDescription($snapshot->description());
            $a->updateVersion();

            // 保存
            $this->agencyRepository->save($a);

            // スナップショット保存（ロールバック後の状態を保存）
            $newSnapshot = $this->snapshotFactory->create($a);
            $this->snapshotRepository->save($newSnapshot);

            // 履歴記録
            $history = $this->agencyHistoryFactory->create(
                actionType: HistoryActionType::Rollback,
                editorIdentifier: $input->principalIdentifier(),
                submitterIdentifier: null,
                agencyIdentifier: $a->agencyIdentifier(),
                draftAgencyIdentifier: null,
                fromStatus: null,
                toStatus: null,
                fromVersion: $baseVersion,
                toVersion: $targetVersion,
                subjectName: $a->name(),
            );
            $this->agencyHistoryRepository->save($history);

            $rolledBackAgencies[] = $a;
        }

        return $rolledBackAgencies;
    }
}
