<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;

readonly class PublishSong implements PublishSongInterface
{
    public function __construct(
        private SongRepositoryInterface         $songRepository,
        private SongServiceInterface            $songService,
        private SongFactoryInterface            $songFactory,
        private SongHistoryRepositoryInterface  $songHistoryRepository,
        private SongHistoryFactoryInterface     $songHistoryFactory,
        private SongSnapshotFactoryInterface    $songSnapshotFactory,
        private SongSnapshotRepositoryInterface $songSnapshotRepository,
        private PrincipalRepositoryInterface    $principalRepository,
    ) {
    }

    /**
     * @param PublishSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedSongException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishSongInputPort $input): Song
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        if ($song->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::PUBLISH, $resource, $principal)) {
            throw new UnauthorizedException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->songService->existsApprovedButNotTranslatedSong(
            $song->translationSetIdentifier(),
            $song->songIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedSongException();
        }

        if ($song->publishedSongIdentifier()) {
            $publishedSong = $this->songRepository->findById($input->publishedSongIdentifier());
            if ($publishedSong === null) {
                throw new SongNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->songSnapshotFactory->create($publishedSong);
            $this->songSnapshotRepository->save($snapshot);

            $publishedSong->setName($song->name());
            $publishedSong->updateVersion();
        } else {
            $publishedSong = $this->songFactory->create(
                $song->translationSetIdentifier(),
                $song->language(),
                $song->name(),
            );
        }
        if ($song->agencyIdentifier()) {
            $publishedSong->setAgencyIdentifier($song->agencyIdentifier());
        }
        if ($song->groupIdentifier()) {
            $publishedSong->setGroupIdentifier($song->groupIdentifier());
        }
        if ($song->talentIdentifier()) {
            $publishedSong->setTalentIdentifier($song->talentIdentifier());
        }
        $publishedSong->setLyricist($song->lyricist());
        $publishedSong->setComposer($song->composer());
        if ($song->releaseDate()) {
            $publishedSong->setReleaseDate($song->releaseDate());
        }
        $publishedSong->setOverView($song->overView());
        if ($song->coverImagePath()) {
            $publishedSong->setCoverImagePath($song->coverImagePath());
        }
        if ($song->musicVideoLink()) {
            $publishedSong->setMusicVideoLink($song->musicVideoLink());
        }

        $this->songRepository->save($publishedSong);

        $history = $this->songHistoryFactory->create(
            $input->principalIdentifier(),
            $song->editorIdentifier(),
            $song->publishedSongIdentifier(),
            $song->songIdentifier(),
            $song->status(),
            null,
            $song->name(),
        );
        $this->songHistoryRepository->save($history);

        $this->songRepository->deleteDraft($song);

        return $publishedSong;
    }
}
