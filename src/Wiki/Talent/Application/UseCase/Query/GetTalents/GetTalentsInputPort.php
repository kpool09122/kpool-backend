<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Query\GetTalents;

use Source\Shared\Domain\ValueObject\Language;

interface GetTalentsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function language(): Language;
}
