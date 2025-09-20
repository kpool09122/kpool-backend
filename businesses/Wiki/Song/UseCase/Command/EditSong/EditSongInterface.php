<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\EditSong;

use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\UseCase\Exception\SongNotFoundException;

interface EditSongInterface
{
    /**
     * @param EditSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     */
    public function process(EditSongInputPort $input): Song;
}
