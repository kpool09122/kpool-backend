<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\CreateSong;

use Businesses\Wiki\Song\Domain\Entity\Song;

interface CreateSongInterface
{
    public function process(CreateSongInputPort $input): Song;
}
