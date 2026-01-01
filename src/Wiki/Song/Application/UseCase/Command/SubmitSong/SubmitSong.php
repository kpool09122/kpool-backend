<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;

readonly class SubmitSong implements SubmitSongInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private SongHistoryRepositoryInterface $songHistoryRepository,
        private SongHistoryFactoryInterface $songHistoryFactory,
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param SubmitSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitSongInputPort $input): DraftSong
    {
        $song = $this->songRepository->findDraftById($input->songIdentifier());

        if ($song === null) {
            throw new SongNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::SONG,
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($song->status() !== ApprovalStatus::Pending
        && $song->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $previousStatus = $song->status();
        $song->setStatus(ApprovalStatus::UnderReview);

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
