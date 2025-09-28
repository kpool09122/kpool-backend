<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface RejectUpdatedSongInterface
{
    /**
     * @param RejectUpdatedSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedSongInputPort $input): DraftSong;
}
