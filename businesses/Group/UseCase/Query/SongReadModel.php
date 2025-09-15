<?php

namespace Businesses\Group\UseCase\Query;

use DateTimeImmutable;

readonly class SongReadModel
{
    public function __construct(
        private string $songId,
        private string $name,
        private DateTimeImmutable $releaseDate,
        private string $youtubeLink,
        private string $imageUrl,
    ) {
    }

    public function songId(): string
    {
        return $this->songId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function releaseDate(): DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function youtubeLink(): string
    {
        return $this->youtubeLink;
    }

    public function imageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'song_id' => $this->songId,
            'name' => $this->name,
            'release_date' => $this->releaseDate->format('Y-m'),
            'youtube_link' => $this->youtubeLink,
            'image_url' => $this->imageUrl,
        ];
    }
}
