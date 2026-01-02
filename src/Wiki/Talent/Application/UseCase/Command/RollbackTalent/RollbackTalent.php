<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidRollbackTargetVersionException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\SnapshotNotFoundException;
use Source\Wiki\Shared\Domain\Exception\VersionMismatchException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;

readonly class RollbackTalent implements RollbackTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
        private TalentSnapshotRepositoryInterface $snapshotRepository,
        private TalentSnapshotFactoryInterface $snapshotFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private TalentHistoryFactoryInterface $talentHistoryFactory,
        private TalentHistoryRepositoryInterface $talentHistoryRepository,
    ) {
    }

    /**
     * @param RollbackTalentInputPort $input
     * @return Talent[]
     * @throws TalentNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackTalentInputPort $input): array
    {
        // 1. Talent存在確認
        $talent = $this->talentRepository->findById($input->talentIdentifier());
        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        // 2. Principal存在確認と権限チェック
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $groupIds = array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        );

        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: $talent->agencyIdentifier() ? (string) $talent->agencyIdentifier() : null,
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::ROLLBACK, $resourceIdentifier)) {
            throw new DisallowedException();
        }

        // 3. targetVersionが現在のバージョンより前であることを確認
        $targetVersion = $input->targetVersion();
        if (! $talent->isVersionGreaterThan($targetVersion)) {
            throw new InvalidRollbackTargetVersionException();
        }

        // 4. 翻訳セット内の全Talentを取得
        $allTalents = $this->talentRepository->findByTranslationSetIdentifier(
            $talent->translationSetIdentifier()
        );

        // 5. バージョン一致チェック（Entityメソッド使用）
        $baseVersion = $talent->version();
        foreach ($allTalents as $t) {
            if (! $t->hasSameVersion($baseVersion)) {
                throw new VersionMismatchException();
            }
        }

        // 6. 翻訳セット内の全Snapshotを一括取得（N+1解消）
        $snapshots = $this->snapshotRepository->findByTranslationSetIdentifierAndVersion(
            $talent->translationSetIdentifier(),
            $targetVersion
        );

        // TalentIdentifierをキーにしたマップを作成
        $snapshotMap = [];
        foreach ($snapshots as $snapshot) {
            $snapshotMap[(string) $snapshot->talentIdentifier()] = $snapshot;
        }

        // 7. 各Talentをロールバック
        $rolledBackTalents = [];
        foreach ($allTalents as $t) {
            $snapshot = $snapshotMap[(string) $t->talentIdentifier()] ?? null;
            if ($snapshot === null) {
                throw new SnapshotNotFoundException();
            }

            // Talentを復元（バージョンは新規インクリメント）
            $t->setName($snapshot->name());
            $t->setRealName($snapshot->realName());
            if ($snapshot->agencyIdentifier() !== null) {
                $t->setAgencyIdentifier($snapshot->agencyIdentifier());
            }
            $t->setGroupIdentifiers($snapshot->groupIdentifiers());
            $t->setBirthday($snapshot->birthday());
            $t->setCareer($snapshot->career());
            $t->setImageLink($snapshot->imageLink());
            $t->setRelevantVideoLinks($snapshot->relevantVideoLinks());
            $t->updateVersion();

            // 保存
            $this->talentRepository->save($t);

            // スナップショット保存（ロールバック後の状態を保存）
            $newSnapshot = $this->snapshotFactory->create($t);
            $this->snapshotRepository->save($newSnapshot);

            // 履歴記録
            $history = $this->talentHistoryFactory->create(
                editorIdentifier: $input->principalIdentifier(),
                submitterIdentifier: null,
                talentIdentifier: $t->talentIdentifier(),
                draftTalentIdentifier: null,
                fromStatus: null,
                toStatus: null,
                subjectName: $t->name(),
            );
            $this->talentHistoryRepository->save($history);

            $rolledBackTalents[] = $t;
        }

        return $rolledBackTalents;
    }
}
