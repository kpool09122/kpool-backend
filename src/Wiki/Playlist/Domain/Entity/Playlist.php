<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class Playlist
{
    /**
     * @param list<PlaylistItem> $items
     * @throws PlaylistItemLimitExceededException
     * @throws InvalidArgumentException
     */
    public function __construct(
        private PlaylistIdentifier $playlistIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private PlaylistName $name,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $items = [],
    ) {
        if (count($items) > 50) {
            throw new PlaylistItemLimitExceededException();
        }
        $videos = [];
        $identifiers = [];
        $positions = [];
        foreach ($items as $item) {
            $video = $item->provider()->value . ':' . $item->externalVideoId();
            $identifier = (string) $item->playlistItemIdentifier();
            if ((string) $item->playlistIdentifier() !== (string) $playlistIdentifier
                || isset($videos[$video]) || isset($identifiers[$identifier]) || isset($positions[$item->position()])) {
                throw new InvalidArgumentException('Playlist items must belong to this playlist and be unique.');
            }
            $videos[$video] = true;
            $identifiers[$identifier] = true;
            $positions[$item->position()] = true;
        }
    }

    public function playlistIdentifier(): PlaylistIdentifier
    {
        return $this->playlistIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function name(): PlaylistName
    {
        return $this->name;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<PlaylistItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function videoCount(): int
    {
        return count($this->items);
    }
}
