<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\ValueObject\SongName;

interface SongFactoryInterface
{
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        SongName $name,
    ): Song;
}
