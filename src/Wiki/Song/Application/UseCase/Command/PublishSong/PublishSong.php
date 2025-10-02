<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;

class PublishSong implements PublishSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private SongServiceInterface    $songService,
        private SongFactoryInterface    $songFactory,
    ) {
    }

    /**
     * @param PublishSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedSongException
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
        } else {
            $publishedSong = $this->songFactory->create(
                $song->translationSetIdentifier(),
                $song->translation(),
                $song->name(),
            );
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
        $this->songRepository->deleteDraft($song);

        return $publishedSong;
    }
}
