<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service\Uuid;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class UuidGenerator implements UuidGeneratorInterface
{
    public function generate(): string
    {
        return (string) Uuid::v7();
    }
}
