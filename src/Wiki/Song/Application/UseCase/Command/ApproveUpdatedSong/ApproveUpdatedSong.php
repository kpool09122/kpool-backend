<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveUpdatedSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

class ApproveUpdatedSong implements ApproveUpdatedSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private SongServiceInterface    $songService,
    ) {
    }

    /**
     * @param ApproveUpdatedSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws ExistsApprovedButNotTranslatedSongException
     * @throws InvalidStatusException
     */
    public function process(ApproveUpdatedSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        if ($song->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }


        if ($input->publishedSongIdentifier()) {
            if ($this->songService->existsApprovedButNotTranslatedSong(
                $input->songIdentifier(),
                $input->publishedSongIdentifier(),
            )) {
                throw new ExistsApprovedButNotTranslatedSongException();
            }
        }


        $song->setStatus(ApprovalStatus::Approved);

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
