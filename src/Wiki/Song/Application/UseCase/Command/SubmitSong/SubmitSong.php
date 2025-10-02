<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
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
     */
    public function process(SubmitSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
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
