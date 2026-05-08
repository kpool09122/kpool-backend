<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ListWikisInputPort
{
    public function language(): Language;

    public function perPage(): int;

    public function resourceType(): ?ResourceType;

    public function keyword(): ?string;

    public function sort(): string;

    public function order(): string;
}
