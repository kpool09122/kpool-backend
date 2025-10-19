<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class SubmitSong implements SubmitSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
    ) {
    }

    /**
     * @param SubmitSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(SubmitSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            static fn ($belongIdentifier) => (string) $belongIdentifier,
            $song->belongIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string)$song->agencyIdentifier(),
            groupIds: $groupIds,
        );

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($song->status() !== ApprovalStatus::Pending
        && $song->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $song->setStatus(ApprovalStatus::UnderReview);

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
