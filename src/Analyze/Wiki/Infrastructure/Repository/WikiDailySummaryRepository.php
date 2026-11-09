<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Repository;

use Application\Models\Analyze\RegionalDailySummary as RegionalModel;
use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use DateTimeImmutable;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\Repository\WikiDailySummaryRepositoryInterface;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\Uid\Uuid;

/** @phpstan-import-type StoredProvenance from SummaryModel */
readonly class WikiDailySummaryRepository implements WikiDailySummaryRepositoryInterface
{
    /**
     * @return list<WikiDailySummary>
     * @throws InvalidDailySummaryException
     * @throws MetricDefinitionMismatchException
     */
    public function findAllByDay(UtcDate $day): array
    {
        $rows = SummaryModel::query()->where('day', (string) $day)->get();
        $regionsByTarget = [];
        foreach (RegionalModel::query()->where('context', 'wiki')->where('day', (string) $day)->get() as $region) {
            $regionsByTarget[implode("\0", [$region->wiki_identifier, $region->language, $region->country])][] = new RegionalMetricObservation(
                $region->country,
                $region->region,
                $region->metric,
                $region->metric === 'engagement_seconds'
                    ? ($region->value ?? throw new InvalidDailySummaryException('Regional engagement time is missing.'))
                    : $this->count($region->value),
                $region->denominator,
                $region->quality,
                $region->wiki_identifier,
                $region->translation_set_identifier,
                $region->language,
            );
        }
        $result = [];
        foreach ($rows as $row) {
            $provenance = [];
            foreach ([Metric::VIEWS, Metric::VIEWERS, Metric::ENGAGEMENT_SECONDS, Metric::SUBMISSIONS, Metric::PUBLICATIONS] as $metric) {
                $metadata = $row->measurements[$metric->value] ?? throw new MetricDefinitionMismatchException('Summary metric provenance is missing.');
                $provenance[$metric->value] = $this->restoreProvenance($metadata, $day);
            }
            $result[] = new WikiDailySummary(
                new WikiDailySummaryIdentifier($row->id),
                $day,
                new WikiIdentifier($row->wiki_identifier),
                new TranslationSetIdentifier($row->translation_set_identifier),
                Language::from($row->language),
                $row->country === '' ? null : CountryCode::from($row->country),
                views: new WikiViewCount($this->count($row->views), $provenance[Metric::VIEWS->value]),
                viewers: new WikiViewerCount($this->count($row->viewers), $provenance[Metric::VIEWERS->value]),
                engagementTime: new WikiEngagementTime($row->engagement_seconds ?? throw new InvalidDailySummaryException('Engagement time is missing.'), $row->engagement_denominator, $provenance[Metric::ENGAGEMENT_SECONDS->value]),
                submissions: new WikiSubmissionCount($this->count($row->submissions), $provenance[Metric::SUBMISSIONS->value]),
                publications: new WikiPublicationCount($this->count($row->publications), $provenance[Metric::PUBLICATIONS->value]),
                regions: $regionsByTarget[implode("\0", [$row->wiki_identifier, $row->language, $row->country])] ?? [],
            );
        }

        return $result;
    }

    public function save(WikiDailySummary $summary): void
    {
        $metadata = [];
        $provenance = [
            $summary->views()->provenance(),
            $summary->viewers()->provenance(),
            $summary->engagementTime()->provenance(),
            $summary->submissions()->provenance(),
            $summary->publications()->provenance(),
        ];
        foreach ($provenance as $origin) {
            $metadata[$origin->definition()->metric->value] = $this->serializeProvenance($origin);
        }
        SummaryModel::query()->updateOrCreate(['id' => (string) $summary->identifier()], [
            'day' => (string) $summary->day(),
            'revision' => (string) Uuid::v7(),
            'wiki_identifier' => (string) $summary->wikiIdentifier(), 'language' => $summary->language()->value, 'country' => $summary->country() === null ? '' : $summary->country()->value,
            'views' => $summary->views()->count(),
            'viewers' => $summary->viewers()->count(),
            'engagement_seconds' => $summary->engagementTime()->totalSeconds(),
            'submissions' => $summary->submissions()->count(),
            'publications' => $summary->publications()->count(),
            'translation_set_identifier' => (string) $summary->translationSetIdentifier(),
            'engagement_denominator' => $summary->engagementTime()->sampleCount(),
            'measurements' => $metadata,
        ]);
        $this->saveRegions($summary);
    }

    private function saveRegions(WikiDailySummary $summary): void
    {
        RegionalModel::query()->where('context', 'wiki')->where('day', (string) $summary->day())
            ->where('wiki_identifier', (string) $summary->wikiIdentifier())
            ->where('language', $summary->language()->value)
            ->where('country', $summary->country() === null ? '' : $summary->country()->value)->delete();
        foreach ($summary->regions() as $region) {
            RegionalModel::query()->insert([
                'context' => 'wiki', 'day' => (string) $summary->day(),
                'country' => $region->country, 'region' => $region->region,
                'wiki_identifier' => (string) $summary->wikiIdentifier(),
                'translation_set_identifier' => (string) $summary->translationSetIdentifier(),
                'language' => $summary->language()->value,
                'metric' => $region->metric,
                'source' => 'ga4', 'definition_version' => '1',
                'value' => $region->value, 'denominator' => $region->denominator,
                'quality' => json_encode($region->quality, JSON_THROW_ON_ERROR),
                'revision' => (string) Uuid::v7(), 'updated_at' => $summary->views()->provenance()->updatedAt()->format('Y-m-d H:i:s.uP'),
            ]);
        }
    }

    /** @throws InvalidDailySummaryException */
    private function count(?string $value): string
    {
        if ($value === null) {
            throw new InvalidDailySummaryException('A required count is missing.');
        }

        return preg_replace('/\.0+$/', '', $value) ?? $value;
    }

    /** @return StoredProvenance
     */
    private function serializeProvenance(MetricProvenance $provenance): array
    {
        $definition = $provenance->definition();

        return [
            'source' => $definition->source, 'metric' => $definition->metric->value,
            'grain' => $definition->grain, 'version' => $definition->version,
            'quality' => $provenance->quality(), 'updated_at' => $provenance->updatedAt()->format('Y-m-d\TH:i:s.uP'),
        ];
    }

    /** @param StoredProvenance $metadata
     */
    private function restoreProvenance(array $metadata, UtcDate $day): MetricProvenance
    {
        $definition = new MetricDefinition(
            $metadata['source'],
            Metric::from($metadata['metric']),
            $metadata['grain'],
            $metadata['version'],
        );

        return new MetricProvenance($definition, $day, new DateTimeImmutable($metadata['updated_at']), $metadata['quality']);
    }
}
