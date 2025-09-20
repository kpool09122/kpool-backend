<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

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
