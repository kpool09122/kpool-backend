<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;

readonly class PublishSong implements PublishSongInterface
{
    public function __construct(
        private SongRepositoryInterface        $songRepository,
        private SongServiceInterface           $songService,
        private SongFactoryInterface           $songFactory,
        private SongHistoryRepositoryInterface $songHistoryRepository,
        private SongHistoryFactoryInterface    $songHistoryFactory,
    ) {
    }

    /**
     * @param PublishSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedSongException
     * @throws UnauthorizedException
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

        $principal = $input->principal();
        $agencyId = (string) $song->agencyIdentifier();
        $belongIds = array_map(
            static fn ($belongIdentifier) => (string) $belongIdentifier,
            $song->belongIdentifiers()
        );
        $resource = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: $agencyId,
            groupIds: $belongIds,
            talentIds: $belongIds,
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
        $publishedSong->setBelongIdentifiers($song->belongIdentifiers());
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
            new EditorIdentifier((string)$input->principal()->principalIdentifier()),
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
