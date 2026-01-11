<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;

readonly class EditSong implements EditSongInterface
{
    public function __construct(
        private DraftSongRepositoryInterface $draftSongRepository,
        private ImageServiceInterface        $imageService,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param EditSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(EditSongInputPort $input): DraftSong
    {
        $song = $this->draftSongRepository->findById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new Resource(
            type: ResourceType::SONG,
            agencyId: (string) $input->agencyIdentifier(),
            groupIds: [(string) $input->groupIdentifier()],
            talentIds: [(string) $input->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::EDIT, $resource)) {
            throw new UnauthorizedException();
        }

        $song->setName($input->name());
        if ($input->agencyIdentifier()) {
            $song->setAgencyIdentifier($input->agencyIdentifier());
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
