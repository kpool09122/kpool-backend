<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ListVersionInconsistentWikisInputPort
{
    public function perPage(): int;

    public function resourceType(): ?ResourceType;

    public function sort(): string;

    public function order(): string;
}
