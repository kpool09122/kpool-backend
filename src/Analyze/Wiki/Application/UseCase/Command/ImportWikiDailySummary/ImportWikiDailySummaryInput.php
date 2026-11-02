<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

readonly class ImportWikiDailySummaryInput implements ImportWikiDailySummaryInputPort
{
    public function __construct(private UtcDate $day, private DateTimeImmutable $now, private int $maxPages = 1000)
    {
        if ($maxPages < 1 || $maxPages > 100000) {
            throw new InvalidArgumentException('Invalid daily page limit.');
        }
    }

    public function day(): UtcDate
    {
        return $this->day;
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function maxPages(): int
    {
        return $this->maxPages;
    }
}
