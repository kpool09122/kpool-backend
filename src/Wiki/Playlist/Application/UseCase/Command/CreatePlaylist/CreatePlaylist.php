<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Playlist\Domain\Factory\PlaylistFactoryInterface;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class CreatePlaylist implements CreatePlaylistInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PlaylistRepositoryInterface $playlistRepository,
        private PlaylistFactoryInterface $playlistFactory,
    ) {
    }

    /**
     * @throws PlaylistLimitExceededException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreatePlaylistInputPort $input, CreatePlaylistOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::CREATE_PLAYLIST, new Resource(type: ResourceType::PLAYLIST))) {
            throw new DisallowedException();
        }
        if ($this->playlistRepository->countByPrincipal($input->principalIdentifier()) >= 3) {
            throw new PlaylistLimitExceededException();
        }
        $playlist = $this->playlistFactory->create($input->principalIdentifier(), $input->name());
        $this->playlistRepository->save($playlist);
        $output->setPlaylist($playlist);
    }
}
