<?php

namespace Businesses\Wiki\Song\UseCase\Command\CreateSong;

use Businesses\Wiki\Song\Domain\Entity\Song;

interface CreateSongInterface
{
    public function process(CreateSongInputPort $input): ?Song;
}
