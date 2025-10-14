<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class CreateSong implements CreateSongInterface
{
    public function __construct(
        private DraftSongFactoryInterface $songFactory,
        private SongRepositoryInterface $songRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param CreateSongInputPort $input
     * @return DraftSong
     * @throws UnauthorizedException
     */
    public function process(CreateSongInputPort $input): DraftSong
    {
        $principal = $input->principal();
        $agencyId = (string) $input->agencyIdentifier();
        $belongIds = array_map(
            static fn ($belongIdentifier) => (string) $belongIdentifier,
            $input->belongIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: $agencyId,
            groupIds: $belongIds,
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $song = $this->songFactory->create(
            $input->editorIdentifier(),
            $input->translation(),
            $input->name()
        );
        if ($input->publishedSongIdentifier()) {
            $publishedSong = $this->songRepository->findById($input->publishedSongIdentifier());
            if ($publishedSong) {
                $song->setPublishedSongIdentifier($publishedSong->songIdentifier());
            }
        }
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

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
