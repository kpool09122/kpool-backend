<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service\Ulid;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Symfony\Component\Uid\Ulid;

class UlidGenerator implements UlidGeneratorInterface
{
    public function generate(): string
    {
        return (string) new Ulid();
    }
}
