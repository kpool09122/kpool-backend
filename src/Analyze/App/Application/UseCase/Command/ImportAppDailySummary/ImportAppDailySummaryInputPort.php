<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use DateTimeImmutable;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

interface ImportAppDailySummaryInputPort
{
    public function day(): UtcDate;

    public function now(): DateTimeImmutable;

    public function maxPages(): int;
}
