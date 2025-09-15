<?php

namespace Businesses\Wiki\Song\Domain\Factory;

use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

interface SongFactoryInterface
{
    /**
     * @param SongName $name
     * @return Song
     */
    public function create(
        SongName          $name,
    ): Song;
}
