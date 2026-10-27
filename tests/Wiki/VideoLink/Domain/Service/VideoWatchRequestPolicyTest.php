<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\Service\VideoWatchRequestPolicy;

class VideoWatchRequestPolicyTest extends TestCase
{
    #[DataProvider('eligibilityCases')]
    public function testEligibility(?int $duration, bool $liked, int $count, ?string $last, ?string $reason): void
    {
        self::assertSame($reason, VideoWatchRequestPolicy::rejectionReason($duration, $liked, $count, $last === null ? null : new DateTimeImmutable($last), new DateTimeImmutable('2026-10-28 00:00:00 UTC')));
    }

    /** @return list<array{?int,bool,int,?string,?string}> */
    public static function eligibilityCases(): array
    {
        return [
            [29,true,2,null,null],
            [30,true,2,null,null],
            [31,true,2,null,'duration_not_eligible'],
            [null,true,2,null,'duration_not_eligible'],
            [30,false,2,null,'like_required'],
            [30,true,3,null,'weekly_limit_reached'],
            [30,true,4,null,'weekly_limit_reached'],
            [30,true,0,'2026-10-21 00:00:01 UTC','resend_not_available'],
            [30,true,0,'2026-10-21 00:00:00 UTC',null],
            [30,true,0,'2026-10-20 23:59:59 UTC',null],
        ];
    }
}
