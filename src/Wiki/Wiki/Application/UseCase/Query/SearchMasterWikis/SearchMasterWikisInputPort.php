<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface SearchMasterWikisInputPort
{
    public function language(): Language;

    public function resourceType(): ResourceType;

    public function keyword(): string;

    public function limit(): int;
}
