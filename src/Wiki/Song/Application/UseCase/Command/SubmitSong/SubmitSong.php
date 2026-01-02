<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongHistoryRepositoryInterface;

readonly class SubmitSong implements SubmitSongInterface
{
    public function __construct(
        private DraftSongRepositoryInterface   $draftSongRepository,
        private SongHistoryRepositoryInterface $songHistoryRepository,
        private SongHistoryFactoryInterface    $songHistoryFactory,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
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
            agencyId: (string) $song->agencyIdentifier(),
            groupIds: [(string) $song->groupIdentifier()],
            talentIds: [(string) $song->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::SUBMIT, $resourceIdentifier)) {
            throw new UnauthorizedException();
        }

        if ($song->status() !== ApprovalStatus::Pending
        && $song->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $previousStatus = $song->status();
        $song->setStatus(ApprovalStatus::UnderReview);

        $this->draftSongRepository->save($song);

        $history = $this->songHistoryFactory->create(
            actionType: HistoryActionType::DraftStatusChange,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $song->editorIdentifier(),
            songIdentifier: $song->publishedSongIdentifier(),
            draftSongIdentifier: $song->songIdentifier(),
            fromStatus: $previousStatus,
            toStatus: $song->status(),
            fromVersion: null,
            toVersion: null,
            subjectName: $song->name(),
        );
        $this->songHistoryRepository->save($history);

        return $song;
    }
}
