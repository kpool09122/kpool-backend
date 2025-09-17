<?php

namespace Businesses\Wiki\Song\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

interface SongFactoryInterface
{
    public function create(
        Translation $translation,
        SongName $name,
    ): Song;
}
