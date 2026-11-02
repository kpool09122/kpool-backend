<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryData;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryDataTest extends TestCase
{
    public function testEachRequiredAnalyticalValueHasItsOwnTypedField(): void
    {
        $values = AnalyzeWikiMetrics::analysisValues(new UtcDate('2020-01-01'));
        $data = new WikiDailySummaryData(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        self::assertSame($values[0], $data->views());
        self::assertSame($values[1], $data->viewers());
        self::assertSame($values[2], $data->engagementTime());
        self::assertSame($values[3], $data->submissions());
        self::assertSame($values[4], $data->publications());
        self::assertSame('69200000-0000-7000-8000-000000000001', (string) $data->wikiIdentifier());
        self::assertSame('69200000-0000-7000-8000-000000000002', (string) $data->translationSetIdentifier());
        self::assertSame(Language::JAPANESE, $data->language());
        self::assertSame(CountryCode::JAPAN, $data->country());
    }
}
