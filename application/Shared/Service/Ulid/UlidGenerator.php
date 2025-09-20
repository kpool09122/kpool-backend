<?php

declare(strict_types=1);

namespace Application\Shared\Service\Ulid;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Symfony\Component\Uid\Ulid;

class UlidGenerator implements UlidGeneratorInterface
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
