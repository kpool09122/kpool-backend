<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Wiki\Song\Domain\Entity\DraftSong;

interface CreateSongInterface
{
    public function process(CreateSongInputPort $input): DraftSong;
}
