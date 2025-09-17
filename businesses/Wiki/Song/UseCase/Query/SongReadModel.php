<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Query;

use DateTimeImmutable;

readonly class SongReadModel
{
    /**
     * @param string $songId
     * @param string $name
     * @param string[] $belongingNames
     * @param string $lyricist
     * @param string $composer
     * @param DateTimeImmutable $releaseDate
     * @param string $overview
     * @param string $musicVideoLink
     * @param string $coverImagePath
     */
    public function __construct(
        private string $songId,
        private string $name,
        private array $belongingNames,
        private string $lyricist,
        private string $composer,
        private DateTimeImmutable $releaseDate,
        private string $overview,
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

    /**
     * @return string[]
     */
    public function belongingNames(): array
    {
        return $this->belongingNames;
    }

    public function lyricist(): string
    {
        return $this->lyricist;
    }

    public function composer(): string
    {
        return $this->composer;
    }

    public function releaseDate(): DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function overview(): string
    {
        return $this->overview;
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
            'belonging_names' => $this->belongingNames,
            'lyricist' => $this->lyricist,
            'composer' => $this->composer,
            'release_date' => $this->releaseDate->format('Y-m'),
            'overview' => $this->overview,
            'music_video_link' => $this->musicVideoLink,
            'cover_image_path' => $this->coverImagePath,
        ];
    }
}
