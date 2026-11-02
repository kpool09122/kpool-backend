<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInputPort;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInterface;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;

readonly class GetWikiDailySummaryReport implements GetWikiDailySummaryReportInterface
{
    /** @throws MetricDefinitionMismatchException */
    public function process(GetWikiDailySummaryReportInputPort $input): WikiDailySummaryReportReadModel
    {
        $definition = $input->definition();
        $column = match ($definition->metric) {
            Metric::VIEWS => 'views', Metric::VIEWERS => 'viewers', Metric::ENGAGEMENT_SECONDS => 'engagement_seconds', Metric::SUBMISSIONS => 'submissions', Metric::PUBLICATIONS => 'publications',
            default => throw new MetricDefinitionMismatchException('The requested metric is not a Wiki metric.'),
        };
        $summaries = SummaryModel::query()
            ->where('measurements->' . $definition->metric->value . '->source', $definition->source)
            ->where('measurements->' . $definition->metric->value . '->metric', $definition->metric->value)
            ->where('measurements->' . $definition->metric->value . '->grain', $definition->grain)
            ->where('measurements->' . $definition->metric->value . '->version', $definition->version)
            ->where('day', '>=', (string) $input->period()->start())->where('day', '<', (string) $input->period()->endExclusive())
            ->whereNotNull($column);
        if ($input->wikiIdentifier() !== null) {
            $summaries->where('wiki_identifier', $input->wikiIdentifier());
        }
        if ($input->translationSetIdentifier() !== null) {
            $summaries->where('translation_set_identifier', $input->translationSetIdentifier());
        }
        if ($input->language() !== null) {
            $summaries->where('language', $input->language());
        }
        if ($input->country() !== null) {
            $summaries->where('country', $input->country());
        }
        $revisionParts = [];
        $qualities = [];
        foreach ((clone $summaries)->get() as $row) {
            $metadata = $row->measurements[$definition->metric->value];
            $revisionParts[] = $row->id . ':' . $row->revision;
            $qualities[$row->day] = array_values(array_unique([...($qualities[$row->day] ?? []), ...$metadata['quality']]));
        }
        sort($revisionParts);
        $revision = $revisionParts === [] ? null : hash('sha256', implode("\0", $revisionParts));
        $denominatorExpression = $definition->metric === Metric::ENGAGEMENT_SECONDS ? 'CASE WHEN COUNT(*) = COUNT(engagement_denominator) THEN SUM(engagement_denominator) ELSE NULL END' : 'NULL' ;
        $daily = (clone $summaries)->select('day')->selectRaw('SUM(' . $column . ') AS value, ' . $denominatorExpression . ' AS denominator, MAX(updated_at) AS updated_at')
            ->groupBy('day')->get()->keyBy('day');
        $days = [];
        foreach ($input->period()->days() as $day) {
            $values = $daily->get((string) $day);
            $days[] = [
                'day' => (string) $day,
                'availability' => $values !== null ? 'measured' : 'missing',
                'value' => $values->value ?? null,
                'denominator' => $values?->denominator,
                'quality' => $qualities[(string) $day] ?? [],
                'updatedAt' => $values?->updated_at?->toIso8601String(),
            ];
        }
        $sum = $daily->isEmpty() ? null : $summaries->selectRaw('COALESCE(SUM(' . $column . '), 0) AS value, ' . $denominatorExpression . ' AS denominator, MAX(updated_at) AS updated_at')->first();
        $total = $sum?->value;
        $denominator = $sum?->denominator;

        return new WikiDailySummaryReportReadModel($definition, $revision, $days, $total, $denominator);
    }
}
