<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Repository;

use Application\Models\Analyze\RegionalDailySummary;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Analyze\Wiki\Infrastructure\Repository\WikiDailySummaryRepository;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\TestCase;

#[Group('useDb')]
class RegionalDailySummaryPersistenceTest extends TestCase
{
    public function testAppRepositoryRestoresRegionsAndReplacesOnlyItsDayIncludingEmptySnapshots(): void
    {
        $repository = new AppDailySummaryRepository();
        $day = new UtcDate('2020-01-01');
        $summary = $this->appSummary($day);
        $summary->replaceAnalysis($summary->applicationUsers(), $summary->registeredUsers(), $summary->countries(), [new RegionalMetricObservation('JP', 'JP-13', 'application_users', '7', quality: ['region_unknown'])]);
        $repository->save($summary);
        $next = $this->appSummary($day->next());
        $next->replaceAnalysis($next->applicationUsers(), $next->registeredUsers(), $next->countries(), $summary->regions());
        $repository->save($next);
        $wiki = $this->wikiSummary($day);
        (new WikiDailySummaryRepository())->save($wiki);

        $restored = $repository->findByDay($day);
        self::assertNotNull($restored);
        self::assertEquals($summary->regions(), $restored->regions());
        $revision = RegionalDailySummary::query()->where('context', 'app')->where('day', (string) $day)->firstOrFail()->revision;
        $restored->replaceAnalysis($restored->applicationUsers(), $restored->registeredUsers(), $restored->countries(), [new RegionalMetricObservation('JP', 'JP-27', 'registered_users', '4')]);
        $repository->save($restored);
        $updated = RegionalDailySummary::query()->where('context', 'app')->where('day', (string) $day)->firstOrFail();
        self::assertSame('identity_history', $updated->source);
        self::assertSame('JP-27', $updated->region);
        self::assertNotSame($revision, $updated->revision);
        $restored->replaceAnalysis($restored->applicationUsers(), $restored->registeredUsers(), $restored->countries(), []);
        $repository->save($restored);
        self::assertSame(2, RegionalDailySummary::query()->count());
        self::assertEquals($next->regions(), $repository->findByDay($day->next())?->regions());
        self::assertEquals($wiki->regions(), (new WikiDailySummaryRepository())->findAllByDay($day)[0]->regions());
    }

    public function testWikiRepositoryKeepsOtherWikiLanguageCountryAndDayRegions(): void
    {
        $repository = new WikiDailySummaryRepository();
        $day = new UtcDate('2020-01-01');
        $target = $this->wikiSummary($day);
        $others = [
            $this->wikiSummary($day, wiki: '69200000-0000-7000-8000-000000000003'),
            $this->wikiSummary($day, language: Language::ENGLISH),
            $this->wikiSummary($day, country: CountryCode::GERMANY),
            $this->wikiSummary($day->next()),
        ];
        foreach ([$target, ...$others] as $summary) {
            $repository->save($summary);
        }
        $restored = array_values(array_filter($repository->findAllByDay($day), static fn (WikiDailySummary $summary): bool => (string) $summary->identifier() === (string) $target->identifier()))[0];
        self::assertEquals($target->regions(), $restored->regions());
        $restored->correctTranslationSet(new TranslationSetIdentifier('69200000-0000-7000-8000-000000000004'));
        $repository->save($restored);
        self::assertSame('69200000-0000-7000-8000-000000000004', RegionalDailySummary::query()->where('wiki_identifier', (string) $target->wikiIdentifier())->where('language', 'ja')->where('country', 'JP')->where('day', (string) $day)->firstOrFail()->translation_set_identifier);
        $restored->replaceRegions([]);
        $repository->save($restored);
        self::assertSame(4, RegionalDailySummary::query()->count());
        foreach ($others as $summary) {
            self::assertTrue(RegionalDailySummary::query()->where('day', (string) $summary->day())->where('wiki_identifier', (string) $summary->wikiIdentifier())->where('language', $summary->language()->value)->where('country', $summary->country()?->value)->exists());
        }
    }

    private function appSummary(UtcDate $day): AppDailySummary
    {
        return (new AppDailySummaryFactory(new UuidGenerator()))->create(...AnalyzeAppMetrics::summaryValues($day));
    }

    private function wikiSummary(UtcDate $day, string $wiki = '69200000-0000-7000-8000-000000000001', Language $language = Language::JAPANESE, CountryCode $country = CountryCode::JAPAN): WikiDailySummary
    {
        $translation = '69200000-0000-7000-8000-000000000002';
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier($wiki), new TranslationSetIdentifier($translation), $language, $country, ...AnalyzeWikiMetrics::analysisValues($day));
        $summary->replaceRegions([new RegionalMetricObservation($country->value, $country === CountryCode::JAPAN ? 'JP-13' : 'DE-BE', 'views', '12', wikiIdentifier: $wiki, translationSetIdentifier: $translation, language: $language->value)]);

        return $summary;
    }
}
