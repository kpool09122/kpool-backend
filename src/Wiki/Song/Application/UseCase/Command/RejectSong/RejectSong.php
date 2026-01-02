<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectSong;

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
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;

readonly class RejectSong implements RejectSongInterface
{
    public function __construct(
        private DraftSongRepositoryInterface   $draftSongRepository,
        private SongHistoryRepositoryInterface $songHistoryRepository,
        private SongHistoryFactoryInterface    $songHistoryFactory,
        private PrincipalRepositoryInterface   $principalRepository,
    ) {
    }

    /**
     * @param RejectSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectSongInputPort $input): DraftSong
    {
        $song = $this->draftSongRepository->findById($input->songIdentifier());

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

        if (! $principal->role()->can(Action::REJECT, $resource, $principal)) {
            throw new UnauthorizedException();
        }

        $previousStatus = $song->status();
        $song->setStatus(ApprovalStatus::Rejected);

        $this->draftSongRepository->save($song);

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
