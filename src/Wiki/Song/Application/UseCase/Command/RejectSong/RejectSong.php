<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

class RejectSong implements RejectSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
    ) {
    }

    /**
     * @param RejectSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        if ($song->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $input->principal();
        $belongIds = array_map(
            static fn ($belongIdentifier) => (string) $belongIdentifier,
            $song->belongIdentifiers()
        );
        $resource = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: $belongIds,
            talentIds: $belongIds,
        );

        if (! $principal->role()->can(Action::REJECT, $resource, $principal)) {
            throw new UnauthorizedException();
        }

        $song->setStatus(ApprovalStatus::Rejected);

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
