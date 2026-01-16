<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RollbackGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class RollbackGroup implements RollbackGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private GroupSnapshotRepositoryInterface $snapshotRepository,
        private GroupSnapshotFactoryInterface $snapshotFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private GroupHistoryFactoryInterface $groupHistoryFactory,
        private GroupHistoryRepositoryInterface $groupHistoryRepository,
    ) {
    }

    /**
     * @param RollbackGroupInputPort $input
     * @return Group[]
     * @throws GroupNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackGroupInputPort $input): array
    {
        // 1. Group存在確認
        $group = $this->groupRepository->findById($input->groupIdentifier());
        if ($group === null) {
            throw new GroupNotFoundException();
        }

        // 2. Principal存在確認と権限チェック
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource)) {
            throw new DisallowedException();
        }

        // 3. targetVersionが現在のバージョンより前であることを確認
        $targetVersion = $input->targetVersion();
        if (! $group->isVersionGreaterThan($targetVersion)) {
            throw new InvalidRollbackTargetVersionException();
        }

        // 4. 翻訳セット内の全Groupを取得
        $allGroups = $this->groupRepository->findByTranslationSetIdentifier(
            $group->translationSetIdentifier()
        );

        // 5. バージョン一致チェック（Entityメソッド使用）
        $baseVersion = $group->version();
        foreach ($allGroups as $g) {
            if (! $g->hasSameVersion($baseVersion)) {
                throw new VersionMismatchException();
            }
        }

        // 6. 翻訳セット内の全Snapshotを一括取得（N+1解消）
        $snapshots = $this->snapshotRepository->findByTranslationSetIdentifierAndVersion(
            $group->translationSetIdentifier(),
            $targetVersion
        );

        // GroupIdentifierをキーにしたマップを作成
        $snapshotMap = [];
        foreach ($snapshots as $snapshot) {
            $snapshotMap[(string) $snapshot->groupIdentifier()] = $snapshot;
        }

        // 7. 各Groupをロールバック
        $rolledBackGroups = [];
        foreach ($allGroups as $g) {
            $snapshot = $snapshotMap[(string) $g->groupIdentifier()] ?? null;
            if ($snapshot === null) {
                throw new SnapshotNotFoundException();
            }

            // Groupを復元（バージョンは新規インクリメント）
            $g->setName($snapshot->name());
            $g->setNormalizedName($snapshot->normalizedName());
            if ($snapshot->agencyIdentifier() !== null) {
                $g->setAgencyIdentifier($snapshot->agencyIdentifier());
            }
            $g->setDescription($snapshot->description());
            $g->updateVersion();

            // 保存
            $this->groupRepository->save($g);

            // スナップショット保存（ロールバック後の状態を保存）
            $newSnapshot = $this->snapshotFactory->create($g);
            $this->snapshotRepository->save($newSnapshot);

            // 履歴記録
            $history = $this->groupHistoryFactory->create(
                actionType: HistoryActionType::Rollback,
                editorIdentifier: $input->principalIdentifier(),
                submitterIdentifier: null,
                groupIdentifier: $g->groupIdentifier(),
                draftGroupIdentifier: null,
                fromStatus: null,
                toStatus: null,
                fromVersion: $baseVersion,
                toVersion: $targetVersion,
                subjectName: $g->name(),
            );
            $this->groupHistoryRepository->save($history);

            $rolledBackGroups[] = $g;
        }

        return $rolledBackGroups;
    }
}
