<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class DeletePlaylist implements DeletePlaylistInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PlaylistRepositoryInterface $playlistRepository,
    ) {
    }

    /**
     * @throws PlaylistNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(DeletePlaylistInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::DELETE_PLAYLIST, new Resource(type: ResourceType::PLAYLIST))) {
            throw new DisallowedException();
        }
        if ($this->playlistRepository->findByIdAndPrincipal($input->playlistIdentifier(), $input->principalIdentifier()) === null) {
            throw new PlaylistNotFoundException();
        }
        $this->playlistRepository->deleteByIdAndPrincipal($input->playlistIdentifier(), $input->principalIdentifier());
    }
}
