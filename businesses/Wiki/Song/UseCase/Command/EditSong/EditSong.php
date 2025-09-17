<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\EditSong;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Businesses\Wiki\Song\UseCase\Exception\SongNotFoundException;

class EditSong implements EditSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param EditSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     */
    public function process(EditSongInputPort $input): Song
    {
        $song = $this->songRepository->findById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $song->setName($input->name());
        $song->setBelongIdentifiers($input->belongIdentifiers());
        $song->setLyricist($input->lyricist());
        $song->setComposer($input->composer());
        if ($input->releaseDate()) {
            $song->setReleaseDate($input->releaseDate());
        }
        $song->setOverView($input->overView());
        if ($input->base64EncodedCoverImage()) {
            $coverImageLink = $this->imageService->upload($input->base64EncodedCoverImage());
            $song->setCoverImagePath($coverImageLink);
        }
        if ($input->musicVideoLink()) {
            $song->setMusicVideoLink($input->musicVideoLink());
        }

        $this->songRepository->save($song);

        return $song;
    }
}
