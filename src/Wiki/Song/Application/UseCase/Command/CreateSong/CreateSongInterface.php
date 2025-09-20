<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Wiki\Song\Domain\Entity\Song;

interface CreateSongInterface
{
    public function process(CreateSongInputPort $input): Song;
}
