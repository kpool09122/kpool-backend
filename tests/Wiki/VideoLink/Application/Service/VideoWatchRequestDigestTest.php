<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigest;

class VideoWatchRequestDigestTest extends TestCase
{
    public function testCarriesWikiIdentityAndSeparateWeeklyAndNewCounts(): void
    {
        $owner = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $videos = [['wikiTitle' => 'Sample', 'videoTitle' => null, 'thumbnailUrl' => null, 'likeCount' => 0, 'requestCount' => 2]];
        $digest = new VideoWatchRequestDigest($owner, Language::KOREAN, 'tl-sample', $videos, 10, 2);
        self::assertSame($owner, $digest->ownerAccountIdentifier);
        self::assertSame(Language::KOREAN, $digest->wikiLanguage);
        self::assertSame('tl-sample', $digest->wikiSlug);
        self::assertSame($videos, $digest->videos);
        self::assertSame(10, $digest->recentRequestCount);
        self::assertSame(2, $digest->newRequestCount);
    }
}
