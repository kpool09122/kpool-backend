<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Wiki\Song\Application\UseCase\Query\SongReadModel;

interface GetSongInterface
{
    public function process(GetSongInputPort $input): SongReadModel;
}
