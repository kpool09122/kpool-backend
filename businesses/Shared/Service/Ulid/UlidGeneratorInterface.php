<?php

declare(strict_types=1);

namespace Businesses\Shared\Service\Ulid;

interface UlidGeneratorInterface
{
    public function generate(): string;
}
