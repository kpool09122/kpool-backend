<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use DateTimeImmutable;

interface VideoWatchRequestDigestQueryServiceInterface
{
    /** @return iterable<VideoWatchRequestDigest> */
    public function findByPeriod(DateTimeImmutable $newSince, DateTimeImmutable $recentSince, DateTimeImmutable $before): iterable;
}
