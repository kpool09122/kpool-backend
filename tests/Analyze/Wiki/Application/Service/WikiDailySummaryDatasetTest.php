<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryData;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryDataset;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryDatasetTest extends TestCase
{
    public function testExistingTargetsAreRemovedAndNewTargetsRemainAvailableForCreation(): void
    {
        $values = AnalyzeWikiMetrics::analysisValues(new UtcDate('2020-01-01'));
        $old = new WikiDailySummaryData(new WikiIdentifier('69200000-0000-7000-8000-000000000010'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000003'), Language::JAPANESE, CountryCode::JAPAN, ...$values);
        $new = new WikiDailySummaryData(new WikiIdentifier('69200000-0000-7000-8000-000000000011'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...$values);
        $dataset = $this->dataset([$old, $new]);
        self::assertSame($old, $dataset->removeFor($this->summary('69200000-0000-7000-8000-000000000010')));
        self::assertSame([$new], $dataset->remaining());
        self::assertNull($dataset->removeFor($this->summary('69200000-0000-7000-8000-000000000010')));
    }

    public function testFormerTargetsAbsentFromTheCompleteDatasetAreMeasuredZero(): void
    {
        $dataset = $this->dataset([]);
        $old = $this->summary('69200000-0000-7000-8000-000000000010');
        $zero = $dataset->zeroFor($old);
        self::assertSame('69200000-0000-7000-8000-000000000010', (string) $zero->wikiIdentifier());
        self::assertSame($old->translationSetIdentifier(), $zero->translationSetIdentifier());
        self::assertSame('0', $zero->views()->count());
        self::assertSame('0', $zero->viewers()->count());
        self::assertSame('0', $zero->engagementTime()->totalSeconds());
        self::assertSame(0, $zero->engagementTime()->sampleCount());
        self::assertSame('0', $zero->submissions()->count());
        self::assertSame('0', $zero->publications()->count());
        self::assertEquals(AnalyzeWikiMetrics::definitions()[0], $zero->views()->provenance()->definition());
    }

    public function testTheCollectionCannotSilentlyOverwriteAnIdenticalTarget(): void
    {
        $values = AnalyzeWikiMetrics::analysisValues(new UtcDate('2020-01-01'));
        $data = new WikiDailySummaryData(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...$values);
        $this->expectException(InvalidDailySummaryException::class);
        $this->dataset([$data, $data]);
    }

    /** @param list<WikiDailySummaryData> $data */
    private function dataset(array $data): WikiDailySummaryDataset
    {
        $values = AnalyzeWikiMetrics::analysisValues(new UtcDate('2020-01-01'));

        return new WikiDailySummaryDataset($data, $values[0]->provenance(), $values[1]->provenance(), $values[2]->provenance(), $values[3]->provenance(), $values[4]->provenance());
    }

    private function summary(string $wikiIdentifier): WikiDailySummary
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);

        return new WikiDailySummary(new WikiDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, new WikiIdentifier($wikiIdentifier), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...$values);
    }
}
