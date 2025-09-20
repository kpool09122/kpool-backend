<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSongs;

use Source\Wiki\Song\Application\UseCase\Query\SongReadModel;

interface GetSongsInterface
{
    /**
     * @param GetSongsInputPort $input
     * @return list<SongReadModel>
     */
    public function process(GetSongsInputPort $input): array;
}
