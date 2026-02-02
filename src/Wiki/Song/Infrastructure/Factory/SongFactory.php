<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;

readonly class SongFactory implements SongFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Slug                     $slug,
        Language                 $language,
        SongName                 $name,
    ): Song {
        return new Song(
            new SongIdentifier($this->generator->generate()),
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            new Version(1),
        );
    }
}
