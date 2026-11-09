<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\Uid\Uuid;

/** Local/test fixtures for the Analyze globe, country summaries and daily tables. */
class AnalyzeSampleSeeder extends Seeder
{
    private const string NAMESPACE = '9a119a00-5100-5000-8000-000000000001';
    private const string MARKER = 'analyze_sample_seeder';
    private const string HISTORY_SUBJECT = 'Analyze sample';

    /** @var list<array{principal: string, identity: string, account: string}> */
    private const array ACCOUNTS = [
        ['principal' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa03', 'identity' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa02', 'account' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa01'],
        ['principal' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa08', 'identity' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa07', 'account' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa06'],
    ];

    /** @var array<string, array{int, int, int, int, int}> Views, viewers, average seconds, submissions, publications. */
    private const array COUNTRIES = [
        'JP' => [420, 180, 90, 3, 2],
        'KR' => [350, 160, 150, 5, 3],
        'US' => [300, 140, 60, 4, 2],
        'BR' => [160, 90, 110, 2, 1],
        'GB' => [130, 70, 80, 2, 1],
        'DE' => [95, 55, 100, 1, 1],
        'FR' => [85, 50, 120, 2, 1],
        'IN' => [240, 130, 45, 1, 1],
        'ID' => [190, 100, 70, 3, 1],
        'TH' => [120, 65, 180, 4, 2],
        'AU' => [75, 40, 95, 1, 1],
        'CA' => [65, 35, 75, 1, 1],
        'ZA' => [40, 20, 55, 1, 0],
        'IS' => [0, 0, 0, 0, 0],
        'NG' => [0, 0, 0, 0, 0],
        '' => [30, 15, 65, 1, 1],
    ];

    /** @var array<string, array<string, string>> */
    private const array TOPICS = [
        'stage' => ['ja' => '分析サンプル・ステージ', 'en' => 'Analyze Sample Stage', 'ko' => '분석 샘플 스테이지'],
        'studio' => ['ja' => '分析サンプル・スタジオ', 'en' => 'Analyze Sample Studio', 'ko' => '분석 샘플 스튜디오'],
    ];

    public function run(): void
    {
        // This guard also applies to direct db:seed --class execution.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (DB::table('account_principals')->whereIn('id', array_column(self::ACCOUNTS, 'principal'))->count() !== count(self::ACCOUNTS)) {
            $this->call([MasterDataSeeder::class, TestAccountSeeder::class]);
        }
        $this->call(AnalyzeAuthorizationSeeder::class);

        $now = CarbonImmutable::now('UTC');
        $end = $now->startOfDay()->subDays(3);
        $start = $end->subDays(27);

        $revision = $this->identifier("revision:{$now->toISOString()}");
        DB::transaction(function () use ($revision, $start): void {
            $this->enableSampleAccounts();
            $wikis = $this->seedWikis($start->subWeek());
            $this->removePreviousSamples(array_column($wikis, 'id'));
            $wikiRows = [];
            $histories = [];
            for ($index = 0; $index < 28; $index++) {
                $day = $start->addDays($index);
                // An absent day differs from a stored day with unavailable metrics.
                if ($index !== 3) {
                    $this->seedAppDay($day, $index, $revision);
                }
                foreach ($wikis as $wikiIndex => $wiki) {
                    foreach (self::COUNTRIES as $country => $profile) {
                        // NG has only one unavailable observation; other days have no observation.
                        if ($country === 'NG' && $index !== 9) {
                            continue;
                        }
                        [$baseViews, $baseViewers, $baseSeconds, $baseSubmissions, $basePublications] = $profile;
                        $factor = $wiki['weight'] * (10 + $index % 5);
                        $viewers = $baseViewers * $factor;
                        $submissions = $baseSubmissions === 0 ? 0 : $baseSubmissions + ($index + $wikiIndex) % 3;
                        $publications = $basePublications === 0 ? 0 : $basePublications + ($index + $wikiIndex) % 2;
                        $metadata = $this->measurements([Metric::VIEWS, Metric::VIEWERS, Metric::ENGAGEMENT_SECONDS, Metric::SUBMISSIONS, Metric::PUBLICATIONS], $day);
                        foreach ([Metric::VIEWS, Metric::VIEWERS, Metric::ENGAGEMENT_SECONDS] as $metric) {
                            if ($index === 9 || $country === 'NG' || ($index === 16 && $country === 'JP' && $metric === Metric::VIEWS)) {
                                $metadata[$metric->value]['version'] = 'sample-unavailable';
                            }
                        }
                        // Show unavailable average time in CA without poisoning the other valid denominators.
                        if ($country === 'CA') {
                            $metadata[Metric::ENGAGEMENT_SECONDS->value]['version'] = 'sample-unavailable';
                        }
                        if ($index !== 3) {
                            $wikiRows[] = [
                                'id' => $this->identifier("summary:{$day->toDateString()}:{$wiki['id']}:$country"),
                                'day' => $day->toDateString(),
                                'revision' => $revision,
                                'wiki_identifier' => $wiki['id'],
                                'translation_set_identifier' => $wiki['translationSet'],
                                'language' => $wiki['language'],
                                'country' => $country,
                                'views' => (string) ($baseViews * $factor),
                                'viewers' => (string) $viewers,
                                'engagement_seconds' => number_format($viewers * ($baseSeconds + (($index + $wikiIndex) % 5) / 4), 3, '.', ''),
                                'engagement_denominator' => $country === 'CA' ? null : $viewers,
                                'submissions' => (string) $submissions,
                                'publications' => (string) $publications,
                                'measurements' => json_encode($metadata, JSON_THROW_ON_ERROR),
                                'created_at' => $day->toDateTimeString(),
                                'updated_at' => $day->addDay()->setTime(6, 0)->toDateTimeString(),
                            ];
                        }
                        foreach (['draft_status_change' => $submissions, 'publish' => $publications] as $action => $count) {
                            for ($event = 0; $event < $count; $event++) {
                                $actor = self::ACCOUNTS[($index + $wikiIndex + $event) % count(self::ACCOUNTS)];
                                $histories[] = [
                                    'id' => $this->identifier("history:{$day->toDateString()}:{$wiki['id']}:$country:$action:$event"),
                                    'action_type' => $action,
                                    'wiki_id' => $wiki['id'],
                                    'translation_set_identifier' => $wiki['translationSet'],
                                    'language' => $wiki['language'],
                                    'resource_type' => 'group',
                                    'actor_id' => $actor['principal'],
                                    'actor_identity_id' => $actor['identity'],
                                    'subject_name' => self::HISTORY_SUBJECT,
                                    'from_status' => $action === 'draft_status_change' ? 'draft' : null,
                                    'to_status' => $action === 'draft_status_change' ? 'under_review' : null,
                                    'from_version' => $action === 'publish' ? 1 : null,
                                    'to_version' => $action === 'publish' ? 2 : null,
                                    'visitor_country' => $country === '' ? null : $country,
                                    'recorded_at' => $day->setTime(12, $event)->toDateTimeString(),
                                ];
                            }
                        }
                    }
                }
            }
            foreach (array_chunk($wikiRows, 500) as $chunk) {
                DB::table('analyze_wiki_daily_summaries')->insertOrIgnore($chunk);
            }
            foreach (array_chunk($histories, 500) as $chunk) {
                DB::table('wiki_histories')->insertOrIgnore($chunk);
            }
        });

        $this->command?->info("Analyze samples: {$start->toDateString()} through {$end->toDateString()} (UTC). Search Wiki names for 分析サンプル / Analyze Sample.");
    }

    private function enableSampleAccounts(): void
    {
        $createPrincipal = app(CreatePrincipalInterface::class);
        foreach (self::ACCOUNTS as $account) {
            if (DB::table('analyze_principals')->where('identity_id', $account['identity'])->where('account_id', $account['account'])->exists()) {
                continue;
            }
            $createPrincipal->process(
                new CreatePrincipalInput(new IdentityIdentifier($account['identity']), new AccountIdentifier($account['account'])),
                new CreatePrincipalOutput(),
            );
        }
    }

    /** @return list<array{id: string, translationSet: string, language: string, weight: int}> */
    private function seedWikis(CarbonImmutable $publishedAt): array
    {
        $wikis = [];
        foreach (array_keys(self::TOPICS) as $topicIndex => $topic) {
            foreach (self::TOPICS[$topic] as $language => $name) {
                $id = $this->identifier("wiki:$topic:$language");
                $translationSet = $this->identifier("translation:$topic");
                DB::table('wikis')->upsert([
                    [
                        'id' => $id, 'translation_set_identifier' => $translationSet, 'language' => $language,
                        'resource_type' => 'group', 'slug' => "gr-analyze-sample-$topic", 'sections' => '[]',
                        'version' => 1, 'editor_id' => self::ACCOUNTS[0]['principal'],
                        'published_at' => $publishedAt, 'created_at' => $publishedAt, 'updated_at' => $publishedAt,
                    ],
                ], ['id']);
                DB::table('wiki_group_basics')->upsert([
                    [
                        'wiki_id' => $id, 'name' => $name, 'normalized_name' => mb_strtolower($name),
                        'group_type' => 'girl_group', 'status' => 'active', 'fandom_name' => '',
                        'official_colors' => '[]', 'emoji' => '', 'representative_symbol' => '',
                        'created_at' => $publishedAt, 'updated_at' => $publishedAt,
                    ],
                ], ['wiki_id']);
                $wikis[] = ['id' => $id, 'translationSet' => $translationSet, 'language' => $language, 'weight' => (2 - $topicIndex) * (['ja' => 3, 'en' => 2, 'ko' => 4][$language])];
            }
        }

        return $wikis;
    }

    /** @param list<string> $wikiIdentifiers */
    private function removePreviousSamples(array $wikiIdentifiers): void
    {
        foreach (['analyze_app_daily_summaries', 'analyze_wiki_daily_summaries'] as $table) {
            DB::table($table)->where('measurements->sample_data->source', self::MARKER)->delete();
        }
        DB::table('wiki_histories')->whereIn('wiki_id', $wikiIdentifiers)->where('subject_name', self::HISTORY_SUBJECT)->delete();
    }

    private function seedAppDay(CarbonImmutable $day, int $index, string $revision): void
    {
        $metadata = $this->measurements([Metric::APPLICATION_USERS, Metric::REGISTERED_USERS], $day);
        if ($index === 9) {
            foreach ([Metric::APPLICATION_USERS, Metric::REGISTERED_USERS] as $metric) {
                $metadata[$metric->value]['version'] = 'sample-unavailable';
            }
        }
        $users = 1800 + $index * 140 + ($index % 7) * 60;
        $registered = 8500 + $index * 28;
        $countries = [];
        $remainingUsers = $users;
        $remainingRegistered = $registered;
        $weights = ['JP' => 28, 'KR' => 23, 'US' => 14, 'BR' => 8, 'DE' => 6, 'IN' => 5, 'ID' => 5, 'GB' => 4, 'CA' => 3, 'IS' => 0];
        foreach ($weights as $country => $weight) {
            $countryUsers = intdiv($users * $weight, 100);
            $countryRegistered = intdiv($registered * $weight, 100);
            $countries[$country] = ['application_users' => (string) $countryUsers, 'registered_users' => (string) $countryRegistered];
            $remainingUsers -= $countryUsers;
            $remainingRegistered -= $countryRegistered;
        }
        $countries[''] = ['application_users' => (string) $remainingUsers, 'registered_users' => (string) $remainingRegistered];
        // Preserve a real imported snapshot if it already occupies this day.
        DB::table('analyze_app_daily_summaries')->insertOrIgnore([
            'id' => $this->identifier("app:{$day->toDateString()}"),
            'day' => $day->toDateString(),
            'revision' => $revision,
            'application_users' => (string) $users,
            'country_metrics' => json_encode($countries, JSON_THROW_ON_ERROR),
            'registered_users' => (string) (8500 + $index * 28),
            'registered_users_snapshot_at' => $day->addDay()->toDateTimeString(),
            'measurements' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => $day->toDateTimeString(),
            'updated_at' => $day->addDay()->setTime(6, 0)->toDateTimeString(),
        ]);
    }

    /** @param list<Metric> $metrics
     * @return array<string, array{source: string, metric: string, grain: string, version: string, quality: list<string>, updated_at: string}>
     */
    private function measurements(array $metrics, CarbonImmutable $day): array
    {
        $metadata = ['sample_data' => [
            'source' => self::MARKER, 'metric' => 'sample_data', 'grain' => 'sample', 'version' => '1',
            'quality' => [], 'updated_at' => $day->addDay()->setTime(6, 0)->toISOString(),
        ]];
        foreach ($metrics as $metric) {
            $metadata[$metric->value] = [
                'source' => match ($metric) {
                    Metric::REGISTERED_USERS => 'identity_history',
                    Metric::SUBMISSIONS, Metric::PUBLICATIONS => 'wiki_history',
                    default => 'ga4',
                },
                'metric' => $metric->value, 'grain' => $metric->grain(), 'version' => '1',
                'quality' => [], 'updated_at' => $day->addDay()->setTime(6, 0)->toISOString(),
            ];
        }

        return $metadata;
    }

    private function identifier(string $key): string
    {
        return Uuid::v5(Uuid::fromString(self::NAMESPACE), $key)->toRfc4122();
    }
}
