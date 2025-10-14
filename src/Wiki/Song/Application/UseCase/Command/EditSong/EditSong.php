<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class EditSong implements EditSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    /**
     * @param EditSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $input->principal();
        $agencyId = (string) $input->agencyIdentifier();
        $belongIds = array_map(
            static fn ($belongIdentifier) => (string) $belongIdentifier,
            $song->belongIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: $agencyId,
            groupIds: $belongIds,
        );

        if (! $principal->role()->can(Action::EDIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $song->setName($input->name());
        if ($input->agencyIdentifier()) {
            $song->setAgencyIdentifier($input->agencyIdentifier());
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
