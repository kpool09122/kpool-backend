<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use DateTimeImmutable;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

interface ImportWikiDailySummaryInputPort
{
    public function day(): UtcDate;

    public function now(): DateTimeImmutable;

    public function maxPages(): int;
}
