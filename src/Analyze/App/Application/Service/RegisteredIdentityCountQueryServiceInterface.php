<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Service;

use DateTimeImmutable;

interface RegisteredIdentityCountQueryServiceInterface
{
    public function countBefore(DateTimeImmutable $endExclusive): string;
}
