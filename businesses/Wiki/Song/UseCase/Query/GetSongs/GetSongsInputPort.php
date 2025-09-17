<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Query\GetSongs;

use Businesses\Shared\ValueObject\Translation;

interface GetSongsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
