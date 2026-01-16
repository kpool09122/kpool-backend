<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
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
    ) {
    }

    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        SongName                  $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftSong {
        return new DraftSong(
            new SongIdentifier($this->generator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->generator->generate()),
            $editorIdentifier,
            $language,
            $name,
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            ApprovalStatus::Pending,
        );
    }
}
