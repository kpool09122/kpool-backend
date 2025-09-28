<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\Song;

interface PublishSongInterface
{
    /**
     * @param PublishSongInputPort $input
     * @return Song
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedSongException
     */
    public function process(PublishSongInputPort $input): Song;
}
