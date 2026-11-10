<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use InvalidArgumentException;

readonly class ReportingPeriod
{
    public function __construct(private UtcDate $start, private UtcDate $endInclusive, DateTimeImmutable $now)
    {
        $first = $start->instant()->modify('first day of this month')->modify('+6 months');
        $anniversary = $first->setDate((int) $first->format('Y'), (int) $first->format('m'), min((int) $start->instant()->format('d'), (int) $first->format('t')));
        $latest = $now->setTimezone(new DateTimeZone('UTC'))->setTime(0, 0)->modify('-3 days');
        if ($endInclusive->instant() < $start->instant() || $endInclusive->next()->instant() > $anniversary || $endInclusive->instant() > $latest) {
            throw new InvalidArgumentException('Expected an inclusive period of at most six calendar months ending no later than UTC D-3.');
        }
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

    public function previous(): ReportingPreviousPeriod
    {
        return new ReportingPreviousPeriod($this);
    }

    /** @return Generator<int, UtcDate> */
    public function days(): Generator
    {
        for ($day = $this->start; $day->instant() < $this->endExclusive()->instant(); $day = $day->next()) {
            yield $day;
        }
    }
}
