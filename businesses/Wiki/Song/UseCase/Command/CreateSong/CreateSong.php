<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\CreateSong;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Businesses\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class CreateSong implements CreateSongInterface
{
    public function __construct(
        private SongFactoryInterface $songFactory,
        private SongRepositoryInterface $songRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    public function process(CreateSongInputPort $input): Song
    {
        $song = $this->songFactory->create($input->translation(), $input->name());
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
