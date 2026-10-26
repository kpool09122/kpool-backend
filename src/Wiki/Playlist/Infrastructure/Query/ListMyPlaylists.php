<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Infrastructure\Query;

use Application\Models\Wiki\Playlist;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInputPort;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutputPort;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistListItemReadModel;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListMyPlaylists implements ListMyPlaylistsInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListMyPlaylistsInputPort $input, ListMyPlaylistsOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::LIST_MY_PLAYLISTS, new Resource(type: ResourceType::PLAYLIST))) {
            throw new DisallowedException();
        }
        $playlists = Playlist::query()
            ->where('principal_id', (string) $input->principalIdentifier())
            ->withCount('items')
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(static fn (Playlist $playlist): PlaylistListItemReadModel => new PlaylistListItemReadModel(
                $playlist->id,
                $playlist->name,
                $playlist->items_count,
                $playlist->created_at->toAtomString(),
                $playlist->updated_at->toAtomString(),
            ))->all();
        $output->output(array_values($playlists));
    }
}
