<?php

declare(strict_types=1);

namespace Tests\Helper;

use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;

final class VisitorLocationTestHelper
{
    /** @return array<string, array{VisitorLocation}> */
    public static function locations(): array
    {
        return [
            'known with leading zero' => [new VisitorLocation('JP', '01')],
            'known alphabetic region' => [new VisitorLocation('US', 'CA')],
            'unregistered codes' => [new VisitorLocation('ZZ', 'NEW9')],
            'country only' => [new VisitorLocation('JP')],
            'unavailable' => [new VisitorLocation()],
        ];
    }
}
