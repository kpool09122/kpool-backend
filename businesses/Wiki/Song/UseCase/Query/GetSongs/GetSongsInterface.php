<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Query\GetSongs;

use Businesses\Wiki\Song\UseCase\Query\SongReadModel;

interface GetSongsInterface
{
    /**
     * @param GetSongsInputPort $input
     * @return list<SongReadModel>
     */
    public function process(GetSongsInputPort $input): array;
}
