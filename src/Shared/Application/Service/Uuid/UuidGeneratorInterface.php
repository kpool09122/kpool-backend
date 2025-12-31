<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service\Uuid;

interface UuidGeneratorInterface
{
    public function generate(): string;
}
