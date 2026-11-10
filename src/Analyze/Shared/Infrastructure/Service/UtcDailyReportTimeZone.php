<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Infrastructure\Service;

use DateInvalidTimeZoneException;
use DateTimeZone;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

final class UtcDailyReportTimeZone
{
    public static function matches(string $timeZone, UtcDate $day): bool
    {
        try {
            $zone = new DateTimeZone($timeZone);
        } catch (DateInvalidTimeZoneException) {
            return false;
        }

        $start = $day->instant();
        if ($zone->getOffset($start) !== 0) {
            return false;
        }

        // Include every offset change within the day, not only its midnight offset.
        foreach ($zone->getTransitions($start->getTimestamp(), $day->next()->instant()->getTimestamp()) ?: [] as $transition) {
            if ($transition['offset'] !== 0) {
                return false;
            }
        }

        return true;
    }
}
