<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

class RejectUpdatedSong implements RejectUpdatedSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
    ) {
    }

    /**
     * @param RejectUpdatedSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        if ($song->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $song->setStatus(ApprovalStatus::Rejected);

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
