<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Infrastructure\Service\Ulid\UlidGenerator;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class DraftSongFactory implements DraftSongFactoryInterface
{
    public function __construct(
        private UlidGenerator $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        SongName $name,
    ): DraftSong {
        return new DraftSong(
            new SongIdentifier($this->ulidGenerator->generate()),
            null,
            $editorIdentifier,
            $translation,
            $name,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            ApprovalStatus::Pending,
        );
    }
}
