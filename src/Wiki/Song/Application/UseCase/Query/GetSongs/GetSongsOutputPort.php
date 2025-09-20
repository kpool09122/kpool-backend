<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSongs;

use Source\Wiki\Song\Application\UseCase\Query\SongReadModel;

interface GetSongsOutputPort
{
    /**
     * @param SongReadModel[] $songs
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $songs,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
