<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

abstract readonly class AuthenticationReadModel
{
    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}
