<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Infrastructure\Query;

use Application\Models\Wiki\Playlist;
use Application\Models\Wiki\PlaylistItem;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInputPort;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutputPort;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistDetailReadModel;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistItemReadModel;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class GetMyPlaylist implements GetMyPlaylistInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws PlaylistNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(GetMyPlaylistInputPort $input, GetMyPlaylistOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::GET_MY_PLAYLIST, new Resource(type: ResourceType::PLAYLIST))) {
            throw new DisallowedException();
        }
        $playlist = Playlist::query()
            ->where('principal_id', (string) $input->principalIdentifier())
            ->where('id', (string) $input->playlistIdentifier())
            ->first();
        if ($playlist === null) {
            throw new PlaylistNotFoundException();
        }
        $items = $playlist->items()->orderBy('position')->get()
            ->map(static fn (PlaylistItem $item): PlaylistItemReadModel => new PlaylistItemReadModel(
                $item->video_link_id,
                $item->provider,
                $item->external_video_id,
                $item->position,
                $item->url,
                $item->title,
                $item->thumbnail_path !== null ? ImageUrl::fromPath($item->thumbnail_path) : $item->thumbnail_url,
                $item->duration_seconds,
            ))->all();
        $output->output(new PlaylistDetailReadModel(
            $playlist->id,
            $playlist->name,
            $playlist->created_at->toAtomString(),
            $playlist->updated_at->toAtomString(),
            array_values($items),
        ));
    }
}
