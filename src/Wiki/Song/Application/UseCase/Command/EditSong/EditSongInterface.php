<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface EditSongInterface
{
    /**
     * @param EditSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditSongInputPort $input): DraftSong;
}
