<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RollbackSong;

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
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;

readonly class RollbackSong implements RollbackSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private SongSnapshotRepositoryInterface $snapshotRepository,
        private SongSnapshotFactoryInterface $snapshotFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private SongHistoryFactoryInterface $songHistoryFactory,
        private SongHistoryRepositoryInterface $songHistoryRepository,
    ) {
    }

    /**
     * @param RollbackSongInputPort $input
     * @return Song[]
     * @throws SongNotFoundException
     * @throws SnapshotNotFoundException
     * @throws VersionMismatchException
     * @throws InvalidRollbackTargetVersionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function process(RollbackSongInputPort $input): array
    {
        // 1. Song存在確認
        $song = $this->songRepository->findById($input->songIdentifier());
        if ($song === null) {
            throw new SongNotFoundException();
        }

        // 2. Principal存在確認と権限チェック
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::SONG,
            agencyId: $song->agencyIdentifier() ? (string) $song->agencyIdentifier() : null,
            groupIds: $song->groupIdentifier() ? [(string) $song->groupIdentifier()] : [],
            talentIds: $song->talentIdentifier() ? [(string) $song->talentIdentifier()] : [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::ROLLBACK, $resource)) {
            throw new DisallowedException();
        }

        // 3. targetVersionが現在のバージョンより前であることを確認
        $targetVersion = $input->targetVersion();
        if (! $song->isVersionGreaterThan($targetVersion)) {
            throw new InvalidRollbackTargetVersionException();
        }

        // 4. 翻訳セット内の全Songを取得
        $allSongs = $this->songRepository->findByTranslationSetIdentifier(
            $song->translationSetIdentifier()
        );

        // 5. バージョン一致チェック（Entityメソッド使用）
        $baseVersion = $song->version();
        foreach ($allSongs as $s) {
            if (! $s->hasSameVersion($baseVersion)) {
                throw new VersionMismatchException();
            }
        }

        // 6. 翻訳セット内の全Snapshotを一括取得（N+1解消）
        $snapshots = $this->snapshotRepository->findByTranslationSetIdentifierAndVersion(
            $song->translationSetIdentifier(),
            $targetVersion
        );

        // SongIdentifierをキーにしたマップを作成
        $snapshotMap = [];
        foreach ($snapshots as $snapshot) {
            $snapshotMap[(string) $snapshot->songIdentifier()] = $snapshot;
        }

        // 7. 各Songをロールバック
        $rolledBackSongs = [];
        foreach ($allSongs as $s) {
            $snapshot = $snapshotMap[(string) $s->songIdentifier()] ?? null;
            if ($snapshot === null) {
                throw new SnapshotNotFoundException();
            }

            // Songを復元（バージョンは新規インクリメント）
            $s->setName($snapshot->name());
            if ($snapshot->agencyIdentifier() !== null) {
                $s->setAgencyIdentifier($snapshot->agencyIdentifier());
            }
            if ($snapshot->groupIdentifier() !== null) {
                $s->setGroupIdentifier($snapshot->groupIdentifier());
            }
            if ($snapshot->talentIdentifier() !== null) {
                $s->setTalentIdentifier($snapshot->talentIdentifier());
            }
            $s->setLyricist($snapshot->lyricist());
            $s->setComposer($snapshot->composer());
            if ($snapshot->releaseDate() !== null) {
                $s->setReleaseDate($snapshot->releaseDate());
            }
            $s->setOverView($snapshot->overView());
            if ($snapshot->coverImagePath() !== null) {
                $s->setCoverImagePath($snapshot->coverImagePath());
            }
            if ($snapshot->musicVideoLink() !== null) {
                $s->setMusicVideoLink($snapshot->musicVideoLink());
            }
            $s->updateVersion();

            // 保存
            $this->songRepository->save($s);

            // スナップショット保存（ロールバック後の状態を保存）
            $newSnapshot = $this->snapshotFactory->create($s);
            $this->snapshotRepository->save($newSnapshot);

            // 履歴記録
            $history = $this->songHistoryFactory->create(
                actionType: HistoryActionType::Rollback,
                editorIdentifier: $input->principalIdentifier(),
                submitterIdentifier: null,
                songIdentifier: $s->songIdentifier(),
                draftSongIdentifier: null,
                fromStatus: null,
                toStatus: null,
                fromVersion: $baseVersion,
                toVersion: $targetVersion,
                subjectName: $s->name(),
            );
            $this->songHistoryRepository->save($history);

            $rolledBackSongs[] = $s;
        }

        return $rolledBackSongs;
    }
}
