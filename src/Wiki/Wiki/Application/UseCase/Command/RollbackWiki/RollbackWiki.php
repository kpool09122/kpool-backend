<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

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
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class RollbackWiki implements RollbackWikiInterface
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository,
        private WikiSnapshotRepositoryInterface $snapshotRepository,
        private WikiSnapshotFactoryInterface $snapshotFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private WikiHistoryFactoryInterface $wikiHistoryFactory,
        private WikiHistoryRepositoryInterface $wikiHistoryRepository,
    ) {
    }

    /**
     * @param RollbackWikiInputPort $input
     * @param RollbackWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackWikiInputPort $input, RollbackWikiOutputPort $output): void
    {
        // 1. Wiki存在確認
        $wiki = $this->wikiRepository->findById($input->wikiIdentifier());
        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        // 2. Principal存在確認と権限チェック
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: $input->resourceType(),
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->talentIdentifiers(),
            ),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource)) {
            throw new DisallowedException();
        }

        // 3. targetVersionが現在のバージョンより前であることを確認
        $targetVersion = $input->targetVersion();
        if (! $wiki->isVersionGreaterThan($targetVersion)) {
            throw new InvalidRollbackTargetVersionException();
        }

        // 4. 翻訳セット内の全Wikiを取得
        $allWikis = $this->wikiRepository->findByTranslationSetIdentifier(
            $wiki->translationSetIdentifier()
        );

        // 5. バージョン一致チェック（Entityメソッド使用）
        $baseVersion = $wiki->version();
        foreach ($allWikis as $w) {
            if (! $w->hasSameVersion($baseVersion)) {
                throw new VersionMismatchException();
            }
        }

        // 6. 翻訳セット内の全Snapshotを一括取得（N+1解消）
        $snapshots = $this->snapshotRepository->findByTranslationSetIdentifierAndVersion(
            $wiki->translationSetIdentifier(),
            $targetVersion
        );

        // WikiIdentifierをキーにしたマップを作成
        $snapshotMap = [];
        foreach ($snapshots as $snapshot) {
            $snapshotMap[(string) $snapshot->wikiIdentifier()] = $snapshot;
        }

        // 7. 各Wikiをロールバック
        $rolledBackWikis = [];
        foreach ($allWikis as $w) {
            $snapshot = $snapshotMap[(string) $w->wikiIdentifier()] ?? null;
            if ($snapshot === null) {
                throw new SnapshotNotFoundException();
            }

            // Wikiを復元（バージョンは新規インクリメント）
            $w->setBasic($snapshot->basic());
            $w->setSections($snapshot->sections());
            $w->setThemeColor($snapshot->themeColor());
            $w->setEditorIdentifier($snapshot->editorIdentifier());
            $w->setApproverIdentifier($snapshot->approverIdentifier());
            $w->setMergerIdentifier($snapshot->mergerIdentifier());
            $w->setSourceEditorIdentifier($snapshot->sourceEditorIdentifier());
            $w->setMergedAt($snapshot->mergedAt());
            $w->setTranslatedAt($snapshot->translatedAt());
            $w->setApprovedAt($snapshot->approvedAt());
            $w->updateVersion();

            // 保存
            $this->wikiRepository->save($w);

            // スナップショット保存（ロールバック後の状態を保存）
            $newSnapshot = $this->snapshotFactory->create($w);
            $this->snapshotRepository->save($newSnapshot);

            // 履歴記録
            $history = $this->wikiHistoryFactory->create(
                actionType: HistoryActionType::Rollback,
                actorIdentifier: $input->principalIdentifier(),
                submitterIdentifier: null,
                wikiIdentifier: $w->wikiIdentifier(),
                draftWikiIdentifier: null,
                fromStatus: null,
                toStatus: null,
                fromVersion: $baseVersion,
                toVersion: $targetVersion,
                subjectName: $w->basic()->name(),
            );
            $this->wikiHistoryRepository->save($history);

            $rolledBackWikis[] = $w;
        }

        $output->setWikis($rolledBackWikis);
    }
}
