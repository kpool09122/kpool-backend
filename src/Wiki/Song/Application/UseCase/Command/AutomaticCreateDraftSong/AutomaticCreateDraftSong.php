<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\AutomaticDraftSongCreationServiceInterface;

readonly class AutomaticCreateDraftSong implements AutomaticCreateDraftSongInterface
{
    public function __construct(
        private AutomaticDraftSongCreationServiceInterface $automaticDraftSongCreationService,
        private SongRepositoryInterface $songRepository,
    ) {
    }

    /**
     * @param AutomaticCreateDraftSongInputPort $input
     * @return DraftSong
     * @throws UnauthorizedException
     */
    public function process(AutomaticCreateDraftSongInputPort $input): DraftSong
    {
        $principal = $input->principal();

        $role = $principal->role();
        if ($role !== Role::ADMINISTRATOR && $role !== Role::SENIOR_COLLABORATOR) {
            throw new UnauthorizedException();
        }

        $draftSong = $this->automaticDraftSongCreationService->create($input->payload(), $principal);
        $this->songRepository->saveDraft($draftSong);

        return $draftSong;
    }
}
