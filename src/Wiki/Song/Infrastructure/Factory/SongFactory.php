<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
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
        private UuidGeneratorInterface        $generator,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        SongName                 $name,
    ): Song {
        $normalizedName = $this->normalizationService->normalize((string) $name, $language);
        $lyricist = new Lyricist('');
        $normalizedLyricist = $this->normalizationService->normalize((string) $lyricist, $language);
        $composer = new Composer('');
        $normalizedComposer = $this->normalizationService->normalize((string) $composer, $language);

        return new Song(
            new SongIdentifier($this->generator->generate()),
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            null,
            null,
            null,
            $lyricist,
            $normalizedLyricist,
            $composer,
            $normalizedComposer,
            null,
            new Overview(''),
            null,
            null,
            new Version(1),
        );
    }
}
