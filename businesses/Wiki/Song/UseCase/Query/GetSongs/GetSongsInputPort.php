<?php

namespace Businesses\Wiki\Song\UseCase\Query\GetSongs;

interface GetSongsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;
}
