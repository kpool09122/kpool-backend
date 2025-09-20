<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Query;

use DateTimeImmutable;

readonly class SongReadModel
{
    public function __construct(
        private string $songId,
        private string $name,
        private DateTimeImmutable $releaseDate,
        private string $musicVideoLink,
        private string $coverImagePath,
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

    public function musicVideoLink(): string
    {
        return $this->musicVideoLink;
    }

    public function coverImagePath(): string
    {
        return $this->coverImagePath;
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
            'music_video_link' => $this->musicVideoLink,
            'cover_image_path' => $this->coverImagePath,
        ];
    }
}
