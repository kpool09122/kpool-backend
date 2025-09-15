<?php

namespace Businesses\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Wiki\Song\UseCase\Query\SongReadModel;

interface GetSongInterface
{
    public function process(GetSongInputPort $input): SongReadModel;
}
