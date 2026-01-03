<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\MergeSong;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;

readonly class MergeSong implements MergeSongInterface
{
    public function __construct(
        private DraftSongRepositoryInterface $draftSongRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
    ) {
    }

    /**
     * @param MergeSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeSongInputPort $input): DraftSong
    {
        $song = $this->draftSongRepository->findById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: $input->groupIdentifier() ? [(string) $input->groupIdentifier()] : [],
            talentIds: $input->talentIdentifier() ? [(string) $input->talentIdentifier()] : [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::MERGE, $resourceIdentifier)) {
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
        if ($input->musicVideoLink()) {
            $song->setMusicVideoLink($input->musicVideoLink());
        }
        $song->setMergerIdentifier($input->principalIdentifier());
        $song->setMergedAt($input->mergedAt());

        $this->draftSongRepository->save($song);

        return $song;
    }
}
