<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Factory\SongSnapshotFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;

readonly class SongSnapshotFactory implements SongSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(Song $song): SongSnapshot
    {
        return new SongSnapshot(
            new SongSnapshotIdentifier($this->ulidGenerator->generate()),
            $song->songIdentifier(),
            $song->translationSetIdentifier(),
            $song->language(),
            $song->name(),
            $song->agencyIdentifier(),
            $song->belongIdentifiers(),
            $song->lyricist(),
            $song->composer(),
            $song->releaseDate(),
            $song->overView(),
            $song->coverImagePath(),
            $song->musicVideoLink(),
            $song->version(),
            new DateTimeImmutable('now'),
        );
    }
}
