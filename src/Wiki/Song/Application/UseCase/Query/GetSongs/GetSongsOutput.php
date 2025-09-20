<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSongs;

use Source\Wiki\Song\Application\UseCase\Query\SongReadModel;

class GetSongsOutput implements GetSongsOutputPort
{
    /**
     * @var SongReadModel[]
     */
    private array $songs = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

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
    ): void {
        $this->songs = $songs;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'songs' => array_map(static fn (SongReadModel $song) => $song->toArray(), $this->songs),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}
