<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationCount;

class DailyWikiOperationCountTest extends TestCase
{
    public function testCountsSubmissionsAndPublicationsIndependentlyForTheTarget(): void
    {
        $count = new DailyWikiOperationCount('wiki', 'translation-set', 'ja', 'JP');
        self::assertSame('0', $count->submissions());
        self::assertSame('0', $count->publications());
        $count->recordSubmission();
        $count->recordSubmission();
        $count->recordPublication();
        self::assertSame('2', $count->submissions());
        self::assertSame('1', $count->publications());
        self::assertSame('wiki', $count->wikiIdentifier());
        self::assertSame('translation-set', $count->translationSetIdentifier());
        self::assertSame('ja', $count->language());
        self::assertSame('JP', $count->country());
    }
}
