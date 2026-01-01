<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;

readonly class ApproveSong implements ApproveSongInterface
{
    public function __construct(
        private SongRepositoryInterface        $songRepository,
        private SongServiceInterface           $songService,
        private SongHistoryRepositoryInterface $songHistoryRepository,
        private SongHistoryFactoryInterface    $songHistoryFactory,
        private PrincipalRepositoryInterface   $principalRepository,
    ) {
    }

    /**
     * @param ApproveSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws ExistsApprovedButNotTranslatedSongException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
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

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::APPROVE, $resource, $principal)) {
            throw new UnauthorizedException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->songService->existsApprovedButNotTranslatedSong(
            $song->translationSetIdentifier(),
            $song->songIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedSongException();
        }

        $previousStatus = $song->status();
        $song->setStatus(ApprovalStatus::Approved);

        $this->songRepository->saveDraft($song);

        $history = $this->songHistoryFactory->create(
            $input->principalIdentifier(),
            $song->editorIdentifier(),
            $song->publishedSongIdentifier(),
            $song->songIdentifier(),
            $previousStatus,
            $song->status(),
            $song->name(),
        );
        $this->songHistoryRepository->save($history);

        return $song;
    }
}
