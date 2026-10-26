<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Playlist\Application\Service\PlaylistVideoSourceServiceInterface;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SavePlaylist implements SavePlaylistInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private PlaylistRepositoryInterface $playlistRepository,
        private PlaylistVideoSourceServiceInterface $playlistVideoSourceService,
    ) {
    }

    public function process(SavePlaylistInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($principal, Action::SAVE_PLAYLIST, new Resource(type: ResourceType::PLAYLIST))) {
            throw new DisallowedException();
        }
        $playlist = $this->playlistRepository->findByIdAndPrincipal($input->playlistIdentifier(), $input->principalIdentifier());
        if ($playlist === null) {
            throw new PlaylistNotFoundException();
        }
        if (count($input->items()) > 50) {
            throw new PlaylistItemLimitExceededException();
        }
        $byVideo = [];
        foreach ($playlist->items() as $item) {
            $byVideo[$item->provider()->value . ':' . $item->externalVideoId()] = $item;
        }
        $videoLinkIdentifiers = [];
        foreach ($input->items() as $selection) {
            if ($selection->videoLinkIdentifier !== null) {
                $videoLinkIdentifiers[] = $selection->videoLinkIdentifier;
            }
        }
        $resolvedItems = $this->playlistVideoSourceService->resolveMany($videoLinkIdentifiers, $input->principalIdentifier(), $playlist->playlistIdentifier());
        $items = [];
        foreach ($input->items() as $selection) {
            if ($selection->provider !== null) {
                $item = $byVideo[$selection->provider->value . ':' . $selection->externalVideoId] ?? null;
                if ($item === null) {
                    throw new PlaylistNotFoundException();
                }
            } else {
                if ($selection->videoLinkIdentifier === null) {
                    throw new InvalidArgumentException('A video link identifier is required for a new playlist item.');
                }
                $resolved = $resolvedItems[(string) $selection->videoLinkIdentifier];
                $item = $byVideo[$resolved->provider()->value . ':' . $resolved->externalVideoId()] ?? $resolved;
            }
            $items[] = $item;
        }
        $playlist->edit($input->name(), $items, new DateTimeImmutable());
        $this->playlistRepository->save($playlist);
    }
}
