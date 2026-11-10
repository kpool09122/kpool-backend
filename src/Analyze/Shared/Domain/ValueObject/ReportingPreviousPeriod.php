<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use Generator;

/** The equal-day UTC period immediately preceding the selected reporting period. */
readonly class ReportingPreviousPeriod
{
    private UtcDate $start;
    private UtcDate $endInclusive;

    public function __construct(ReportingPeriod $period)
    {
        $length = (int) $period->start()->instant()->diff($period->endExclusive()->instant())->days;
        $this->start = new UtcDate($period->start()->instant()->modify("-$length days")->format('Y-m-d'));
        $this->endInclusive = new UtcDate($period->start()->instant()->modify('-1 day')->format('Y-m-d'));
    }

    public function start(): UtcDate
    {
        return $this->start;
    }

    public function endInclusive(): UtcDate
    {
        return $this->endInclusive;
    }

    public function endExclusive(): UtcDate
    {
        return $this->endInclusive->next();
    }

    /** @return Generator<int, UtcDate> */
    public function days(): Generator
    {
        for ($day = $this->start; $day->instant() < $this->endExclusive()->instant(); $day = $day->next()) {
            yield $day;
        }
    }
}
