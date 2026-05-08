<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

interface ListWikisInputPort
{
    public function perPage(): int;

    public function resourceType(): ?string;

    public function keyword(): ?string;

    public function sort(): string;

    public function order(): string;
}
