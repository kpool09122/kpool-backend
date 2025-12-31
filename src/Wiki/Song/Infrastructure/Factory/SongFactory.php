<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class SongFactory implements SongFactoryInterface
{
    public function __construct(
        private UuidGenerator $ulidGenerator,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        SongName                 $name,
    ): Song {
        return new Song(
            new SongIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $language,
            $name,
            null,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            new Version(1),
        );
    }
}
