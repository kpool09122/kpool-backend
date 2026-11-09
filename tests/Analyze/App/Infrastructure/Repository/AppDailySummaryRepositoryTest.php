<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Repository;

use Application\Models\Analyze\App\AppCountryDailySummary as CountryModel;
use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class AppDailySummaryRepositoryTest extends TestCase
{
    public function testRoundTripPreservesIdentityAndEveryAnalyticalValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $summary = $factory->create($values[0], $values[1], $values[2]);
        $summary->replaceAnalysis($summary->applicationUsers(), $summary->registeredUsers(), [new AppCountryMetrics(CountryCode::JAPAN, $values[0], $values[1]), new AppCountryMetrics(null, $values[0], $values[1])], $summary->regions());
        $appDailySummaryRepository = new AppDailySummaryRepository();
        self::assertNull($appDailySummaryRepository->findByDay($day));
        $appDailySummaryRepository->save($summary);
        $appDailySummaryRepository->save($summary);
        $restored = $appDailySummaryRepository->findByDay($day);
        self::assertNotNull($restored);
        self::assertSame((string) $summary->identifier(), (string) $restored->identifier());
        self::assertEquals($values[0], $restored->applicationUsers());
        self::assertSame('10', $restored->registeredUsers()->count());
        self::assertSame(2, CountryModel::query()->count());
        self::assertFalse(Schema::hasColumn('analyze_app_daily_summaries', 'country_metrics'));
        self::assertFalse(Schema::hasColumn('analyze_app_daily_summaries', 'registered_users'));
        self::assertEqualsCanonicalizing($summary->countries(), $restored->countries());
        $updated = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '9']);
        $restored->replaceAnalysis(...$updated, regions: $restored->regions());
        $appDailySummaryRepository->save($restored);
        self::assertSame(1, SummaryModel::query()->count());
        $again = $appDailySummaryRepository->findByDay($day);
        self::assertNotNull($again);
        self::assertSame((string) $summary->identifier(), (string) $again->identifier());
        self::assertSame('9', $again->applicationUsers()->count());
        $again->replaceAnalysis($again->applicationUsers(), $again->registeredUsers(), [new AppCountryMetrics(CountryCode::JAPAN, $updated[0], $updated[1])], $again->regions());
        $appDailySummaryRepository->save($again);
        self::assertSame(['JP'], CountryModel::query()->pluck('country')->all());
        self::assertSame('5', $appDailySummaryRepository->findByDay($day)?->registeredUsers()->count());
    }

    public function testDefinitionMetadataLivesInTheSameRowAndCanChangeWithoutChangingIdentity(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $summary = $factory->create($values[0], $values[1], $values[2]);
        $summary->replaceAnalysis($summary->applicationUsers(), $summary->registeredUsers(), [new AppCountryMetrics(CountryCode::JAPAN, $values[0], $values[1]), new AppCountryMetrics(null, $values[0], $values[1])], $summary->regions());
        $repository = new AppDailySummaryRepository();
        $repository->save($summary);
        $definition = new MetricDefinition('new-source', Metric::APPLICATION_USERS, 'application_day', '2');
        $updated = new ApplicationUserCount('9', new MetricProvenance($definition, $day, AnalyzeAppMetrics::now(), ['verified']));
        $summary->replaceAnalysis($updated, $values[1], $values[2], regions: $summary->regions());
        $repository->save($summary);
        $restored = $repository->findByDay($day);
        self::assertNotNull($restored);
        self::assertSame((string) $summary->identifier(), (string) $restored->identifier());
        self::assertEquals($updated, $restored->applicationUsers());
        self::assertSame(1, SummaryModel::query()->count());
        $metadata = SummaryModel::query()->firstOrFail()->measurements[Metric::APPLICATION_USERS->value];
        self::assertSame('new-source', $metadata['source']);
        self::assertSame('2', $metadata['version']);
        self::assertSame(['verified'], $metadata['quality']);
        self::assertFalse(Schema::hasTable('analyze_app_metric_definitions'));
    }

    public function testFailureSavingASecondEntityRollsBackTheFirst(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $first = $factory->create($values[0], $values[1], $values[2]);
        $appDailySummaryRepository = new AppDailySummaryRepository();
        $appDailySummaryRepository->save($first);
        $first->replaceAnalysis(...AnalyzeAppMetrics::summaryValues($day, ['application_users' => '9']), regions: $first->regions());
        $values = AnalyzeAppMetrics::summaryValues($day->next());
        $second = $factory->create($values[0], $values[1], $values[2]);
        $writes = 0;
        SummaryModel::saving(static function () use (&$writes): void {
            if (++$writes === 2) {
                throw new RuntimeException('Injected failure');
            }
        });

        try {
            DB::transaction(function () use ($appDailySummaryRepository, $first, $second): void {
                $appDailySummaryRepository->save($first);
                $appDailySummaryRepository->save($second);
            });
            self::fail('Expected failure');
        } catch (RuntimeException) {
            $restored = $appDailySummaryRepository->findByDay($day);
            self::assertNotNull($restored);
            self::assertSame('5', $restored->applicationUsers()->count());
            self::assertSame(1, SummaryModel::query()->count());
        } finally {
            SummaryModel::flushEventListeners();
        }
    }
}
