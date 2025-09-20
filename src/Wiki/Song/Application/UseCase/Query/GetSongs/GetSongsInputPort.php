<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSongs;

use Source\Shared\Domain\ValueObject\Translation;

interface GetSongsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function translation(): Translation;
}
