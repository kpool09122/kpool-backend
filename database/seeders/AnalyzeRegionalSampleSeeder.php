<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Shared\Domain\ValueObject\AdministrativeAreaCode;
use stdClass;

/** Regional counterparts of the country samples, using the same dates, Wikis and revisions. */
class AnalyzeRegionalSampleSeeder extends Seeder
{
    private const array COUNTRIES = ['JP', 'US', 'CN'];

    /** @return list<string> */
    public static function regions(string $country): array
    {
        if (! in_array($country, self::COUNTRIES, true)) {
            return [];
        }
        $codes = array_values(array_map(
            static fn (AdministrativeAreaCode $area): string => $area->value,
            array_filter(AdministrativeAreaCode::cases(), static fn (AdministrativeAreaCode $area): bool => $area->countryCode()->value === $country),
        ));

        return [...$codes, ''];
    }

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        DB::transaction(function (): void {
            $app = DB::table('analyze_app_country_daily_summaries as countries')
                ->join('analyze_app_daily_summaries as summaries', 'summaries.day', '=', 'countries.day')
                ->where('summaries.measurements->sample_data->source', AnalyzeSampleSeeder::MARKER)
                ->whereIn('countries.country', self::COUNTRIES)
                ->select('countries.*', 'summaries.revision', 'summaries.measurements', 'summaries.updated_at')->get();
            $wiki = DB::table('analyze_wiki_daily_summaries')
                ->where('measurements->sample_data->source', AnalyzeSampleSeeder::MARKER)
                ->whereIn('country', self::COUNTRIES)->get();
            $revisions = $app->pluck('revision')->merge($wiki->pluck('revision'))->unique()->all();
            DB::table('analyze_regional_daily_summaries')->whereIn('revision', $revisions)->delete();
            foreach ($app as $row) {
                $this->seedDay('app', $row);
            }
            foreach ($wiki as $row) {
                $this->seedDay('wiki', $row);
            }
        });
    }

    private function seedDay(string $context, stdClass $summary): void
    {
        $regions = self::regions($summary->country);
        $weights = array_map(static fn (int $index): int => $index === count($regions) - 2 ? 0 : 1 + $index % 7, array_keys($regions));
        $weightTotal = array_sum($weights);
        $measurements = json_decode($summary->measurements, true, 512, JSON_THROW_ON_ERROR);
        $metrics = $context === 'app'
            ? ['application_users' => Metric::APPLICATION_USERS, 'registered_users' => Metric::REGISTERED_USERS]
            : ['views' => Metric::VIEWS, 'viewers' => Metric::VIEWERS, 'engagement_seconds' => Metric::ENGAGEMENT_SECONDS];
        $rows = [];
        foreach ($regions as $index => $region) {
            $weight = $weights[$index];
            foreach ($metrics as $column => $metric) {
                $value = (string) (int) floor((float) $summary->{$column} * $weight / $weightTotal);
                $denominator = null;
                if ($metric === Metric::ENGAGEMENT_SECONDS) {
                    $denominator = (int) floor((float) $summary->engagement_denominator * $weight / $weightTotal);
                    $average = $summary->engagement_denominator > 0 ? (float) $summary->engagement_seconds / $summary->engagement_denominator : 0;
                    $value = number_format($denominator * ($average + $index % 5 / 4), 3, '.', '');
                }
                if ($measurements[$metric->value]['version'] !== '1') {
                    $value = null;
                    $denominator = null;
                }
                $rows[] = [
                    'context' => $context, 'day' => $summary->day, 'country' => $summary->country, 'region' => $region,
                    'wiki_identifier' => $summary->wiki_identifier ?? '',
                    'translation_set_identifier' => $summary->translation_set_identifier ?? '', 'language' => $summary->language ?? '',
                    'metric' => $column, 'source' => $measurements[$metric->value]['source'], 'definition_version' => $measurements[$metric->value]['version'],
                    'value' => $value, 'denominator' => $denominator, 'quality' => '[]',
                    'revision' => $summary->revision, 'updated_at' => $summary->updated_at,
                ];
            }
        }
        DB::table('analyze_regional_daily_summaries')->insertOrIgnore($rows);
    }
}
