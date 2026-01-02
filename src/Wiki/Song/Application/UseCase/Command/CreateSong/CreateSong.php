<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class CreateSong implements CreateSongInterface
{
    public function __construct(
        private DraftSongFactoryInterface $songFactory,
        private SongRepositoryInterface $songRepository,
        private DraftSongRepositoryInterface $draftSongRepository,
        private ImageServiceInterface $imageService,
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param CreateSongInputPort $input
     * @return DraftSong
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateSongInputPort $input): DraftSong
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $input->agencyIdentifier(),
            groupIds: [(string) $input->groupIdentifier()],
            talentIds: [(string) $input->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $song = $this->songFactory->create(
            $input->principalIdentifier(),
            $input->language(),
            $input->name()
        );
        if ($input->publishedSongIdentifier()) {
            $publishedSong = $this->songRepository->findById($input->publishedSongIdentifier());
            if ($publishedSong) {
                $song->setPublishedSongIdentifier($publishedSong->songIdentifier());
            }
        }
        if ($input->groupIdentifier()) {
            $song->setGroupIdentifier($input->groupIdentifier());
        }
        if ($input->talentIdentifier()) {
            $song->setTalentIdentifier($input->talentIdentifier());
        }
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

        $this->draftSongRepository->save($song);

        return $song;
    }
}
