<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Factory\DraftSongFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class DraftSongFactory implements DraftSongFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        SongName                  $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftSong {
        $normalizedName = $this->normalizationService->normalize((string) $name, $language);
        $lyricist = new Lyricist('');
        $normalizedLyricist = $this->normalizationService->normalize((string) $lyricist, $language);
        $composer = new Composer('');
        $normalizedComposer = $this->normalizationService->normalize((string) $composer, $language);

        return new DraftSong(
            new SongIdentifier($this->generator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->generator->generate()),
            $editorIdentifier,
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
            ApprovalStatus::Pending,
        );
    }
}
