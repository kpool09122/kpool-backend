<?php

namespace Businesses\Wiki\Song\Domain\Factory;

use Application\Shared\Service\Ulid\UlidGenerator;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

readonly class SongFactory implements SongFactoryInterface
{
    public function __construct(
        private UlidGenerator $ulidGenerator,
    ) {
    }

    public function create(
        Translation $translation,
        SongName $name,
    ): Song {
        return new Song(
            new SongIdentifier($this->ulidGenerator->generate()),
            $translation,
            $name,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
        );
    }
}
