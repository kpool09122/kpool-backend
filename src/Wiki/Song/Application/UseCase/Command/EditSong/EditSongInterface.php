<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;

interface EditSongInterface
{
    /**
     * @param EditSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     */
    public function process(EditSongInputPort $input): Song;
}
