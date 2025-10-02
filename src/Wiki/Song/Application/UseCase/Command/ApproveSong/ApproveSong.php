<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;

class ApproveSong implements ApproveSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private SongServiceInterface    $songService,
    ) {
    }

    /**
     * @param ApproveSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws ExistsApprovedButNotTranslatedSongException
     * @throws InvalidStatusException
     */
    public function process(ApproveSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        if ($song->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->songService->existsApprovedButNotTranslatedSong(
            $song->translationSetIdentifier(),
            $song->songIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedSongException();
        }

        $song->setStatus(ApprovalStatus::Approved);

        $this->songRepository->saveDraft($song);

        return $song;
    }
}
