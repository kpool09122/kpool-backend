<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\SongName;

interface DraftSongFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        SongName $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftSong;
}
