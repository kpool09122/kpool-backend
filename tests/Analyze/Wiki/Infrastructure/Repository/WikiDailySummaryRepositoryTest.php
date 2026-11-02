<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Repository;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Analyze\Wiki\Infrastructure\Repository\WikiDailySummaryRepository;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WikiDailySummaryRepositoryTest extends TestCase
{
    public function testRoundTripPreservesIdentityAndEveryAnalyticalValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $summary = $factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        self::assertSame([], $wikiDailySummaryRepository->findAllByDay($day));
        $wikiDailySummaryRepository->save($summary);
        $wikiDailySummaryRepository->save($summary);
        $restored = $wikiDailySummaryRepository->findAllByDay($day);
        self::assertCount(1, $restored);
        self::assertSame((string) $summary->identifier(), (string) $restored[0]->identifier());
        self::assertEquals($values[0], $restored[0]->views());
        self::assertEquals($values[1], $restored[0]->viewers());
        self::assertSame('5.000000000', $restored[0]->engagementTime()->totalSeconds());
        self::assertSame(3, $restored[0]->engagementTime()->sampleCount());
        self::assertEquals($values[3], $restored[0]->submissions());
        self::assertEquals($values[4], $restored[0]->publications());
        $updated = AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '9']);
        $restored[0]->replaceAnalysis(...$updated);
        $wikiDailySummaryRepository->save($restored[0]);
        self::assertSame(1, SummaryModel::query()->count());
        $again = $wikiDailySummaryRepository->findAllByDay($day);
        self::assertSame((string) $summary->identifier(), (string) $again[0]->identifier());
        self::assertSame('9', $again[0]->views()->count());
    }

    public function testUnknownCountryRoundTripsAsNullAndRemainsAnEmptyDatabaseDimension(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(
            new WikiIdentifier('69200000-0000-7000-8000-000000000001'),
            new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'),
            Language::JAPANESE,
            null,
            ...$values,
        );
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        $wikiDailySummaryRepository->save($summary);
        $restored = $wikiDailySummaryRepository->findAllByDay($day)[0];
        self::assertNull($restored->country());
        self::assertEquals($summary->wikiIdentifier(), $restored->wikiIdentifier());
        self::assertSame(Language::JAPANESE, $restored->language());
        self::assertSame('', SummaryModel::query()->firstOrFail()->country);
    }

    public function testDefinitionMetadataLivesInTheSameRowAndCanChangeWithoutChangingIdentity(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $summary = $factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...$values);
        $repository = new WikiDailySummaryRepository();
        $repository->save($summary);
        $definition = new MetricDefinition('new-source', Metric::VIEWS, 'wiki_language_country', '2');
        $updated = new WikiViewCount('9', new MetricProvenance($definition, $day, AnalyzeWikiMetrics::now(), ['verified']));
        $summary->replaceAnalysis($updated, $values[1], $values[2], $values[3], $values[4]);
        $repository->save($summary);
        $restored = $repository->findAllByDay($day)[0];
        self::assertNotNull($restored);
        self::assertSame((string) $summary->identifier(), (string) $restored->identifier());
        self::assertEquals($updated, $restored->views());
        self::assertSame(1, SummaryModel::query()->count());
        $metadata = SummaryModel::query()->firstOrFail()->measurements[Metric::VIEWS->value];
        self::assertSame('new-source', $metadata['source']);
        self::assertSame('2', $metadata['version']);
        self::assertSame(['verified'], $metadata['quality']);
        self::assertFalse(Schema::hasTable('analyze_wiki_metric_definitions'));
    }

    public function testFailureSavingASecondEntityRollsBackTheFirst(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $first = $factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        $wikiDailySummaryRepository->save($first);
        $first->replaceAnalysis(...AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '9']));
        $values = AnalyzeWikiMetrics::analysisValues($day->next());
        $second = $factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $writes = 0;
        SummaryModel::saving(static function () use (&$writes): void {
            if (++$writes === 2) {
                throw new RuntimeException('Injected failure');
            }
        });

        try {
            DB::transaction(function () use ($wikiDailySummaryRepository, $first, $second): void {
                $wikiDailySummaryRepository->save($first);
                $wikiDailySummaryRepository->save($second);
            });
            self::fail('Expected failure');
        } catch (RuntimeException) {
            self::assertSame('5', $wikiDailySummaryRepository->findAllByDay($day)[0]->views()->count());
            self::assertSame(1, SummaryModel::query()->count());
        } finally {
            SummaryModel::flushEventListeners();
        }
    }

    public function testDeletingTheSourceWikiRetainsItsCompleteAnalyticalSummary(): void
    {
        $wikiIdentifier = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        CreateWiki::create($wikiIdentifier, 'group', ['translation_set_identifier' => $translationSetIdentifier]);
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier($wikiIdentifier), new TranslationSetIdentifier($translationSetIdentifier), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        $wikiDailySummaryRepository->save($summary);
        WikiModel::query()->whereKey($wikiIdentifier)->delete();
        $restored = $wikiDailySummaryRepository->findAllByDay($day);
        self::assertCount(1, $restored);
        self::assertSame((string) $summary->identifier(), (string) $restored[0]->identifier());
        self::assertSame($wikiIdentifier, (string) $restored[0]->wikiIdentifier());
        self::assertSame('5', $restored[0]->views()->count());
    }
}
