<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface SearchTranslationSetMasterWikisInputPort
{
    public function resourceType(): ResourceType;

    public function keyword(): string;

    public function limit(): int;
}
