<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class Playlist
{
    /**
     * @param list<PlaylistItem> $items
     * @throws PlaylistItemLimitExceededException
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly PlaylistIdentifier $playlistIdentifier,
        private readonly PrincipalIdentifier $principalIdentifier,
        private PlaylistName $name,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $items = [],
    ) {
        $this->validateItems($items);
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

    /**
     * @param list<PlaylistItem> $items
     * @throws PlaylistItemLimitExceededException
     * @throws InvalidArgumentException
     */
    public function edit(PlaylistName $name, array $items, DateTimeImmutable $updatedAt): void
    {
        $positioned = [];
        foreach ($items as $position => $item) {
            $positioned[] = $item->atPosition(new PlaylistItemPosition($position));
        }

        $this->validateItems($positioned);
        $this->name = $name;
        $this->items = $positioned;
        $this->updatedAt = $updatedAt;
    }

    public function videoCount(): int
    {
        return count($this->items);
    }

    /**
     * @param list<PlaylistItem> $items
     * @throws PlaylistItemLimitExceededException
     * @throws InvalidArgumentException
     */
    private function validateItems(array $items): void
    {
        if (count($items) > 50) {
            throw new PlaylistItemLimitExceededException();
        }
        $videos = [];
        $positions = [];
        foreach ($items as $item) {
            $video = $item->provider()->value . ':' . $item->externalVideoId();
            if ((string) $item->playlistIdentifier() !== (string) $this->playlistIdentifier
                || isset($videos[$video]) || isset($positions[$item->position()->value()])) {
                throw new InvalidArgumentException('Playlist items must belong to this playlist and be unique.');
            }
            $videos[$video] = true;
            $positions[$item->position()->value()] = true;
        }
    }
}
