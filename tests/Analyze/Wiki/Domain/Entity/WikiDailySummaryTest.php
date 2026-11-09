<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryTest extends TestCase
{
    public function testInvalidRegionalReplacementPreservesTheCurrentSnapshot(): void
    {
        $day = new UtcDate('2020-01-01');
        $summary = new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...AnalyzeWikiMetrics::analysisValues($day));
        $row = new RegionalMetricObservation('JP', 'JP-13', 'views', '7', wikiIdentifier: '69200000-0000-7000-8000-000000000001', language: 'ja');
        $summary->replaceRegions([$row]);
        foreach ([[$row, $row], [new RegionalMetricObservation('DE', 'DE-BE', 'views', '7', wikiIdentifier: '69200000-0000-7000-8000-000000000001', language: 'ja')]] as $invalid) {
            try {
                $summary->replaceRegions($invalid);
                self::fail('Invalid regional ownership or duplicate grain must be rejected.');
            } catch (InvalidDailySummaryException) {
                self::assertSame([$row], $summary->regions());
            }
        }
        $summary->replaceRegions([]);
        self::assertSame([], $summary->regions());
    }

    public function testAllAnalyticalValuesExistAtConstructionAndAreReplacedTogether(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $summary = new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        self::assertSame($values[0], $summary->views());
        self::assertSame($values[1], $summary->viewers());
        self::assertSame($values[2], $summary->engagementTime());
        self::assertSame($values[3], $summary->submissions());
        self::assertSame($values[4], $summary->publications());
        $identifier = $summary->identifier();
        $updated = AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '0']);
        $summary->replaceAnalysis(...$updated);
        self::assertSame($identifier, $summary->identifier());
        self::assertSame('0', $summary->views()->count());
    }

    public function testInvalidReplacementDoesNotMutateAnyValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $summary = new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $invalid = AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '9']);
        $invalid[1] = AnalyzeWikiMetrics::analysisValues($day->next())[1];

        try {
            $summary->replaceAnalysis(...$invalid);
            self::fail('A mismatched day must be rejected.');
        } catch (InvalidDailySummaryException) {
            self::assertSame($values[0], $summary->views());
            self::assertSame($values[1], $summary->viewers());
            self::assertSame($values[2], $summary->engagementTime());
            self::assertSame($values[3], $summary->submissions());
            self::assertSame($values[4], $summary->publications());
        }
    }

    public function testInitialValuesMustBelongToTheSummaryDay(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $values[0] = AnalyzeWikiMetrics::analysisValues($day->next())[0];
        $this->expectException(InvalidDailySummaryException::class);
        new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
    }

    public function testTranslationSetCorrectionPreservesIdentityAndAnalyticalValues(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $summary = new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $identifier = $summary->identifier();
        $summary->correctTranslationSet(new TranslationSetIdentifier('69200000-0000-7000-8000-000000000003'));
        self::assertSame($identifier, $summary->identifier());
        self::assertSame('69200000-0000-7000-8000-000000000003', (string) $summary->translationSetIdentifier());
        self::assertSame($values[0], $summary->views());
    }
}
