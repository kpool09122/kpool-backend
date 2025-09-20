<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service\Ulid;

interface UlidGeneratorInterface
{
    public function generate(): string;
}
