<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Source\Analyze\App\Application\Exception\EmptyReportingPeriodException;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInputPort;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInterface;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;

readonly class GetAppDailySummaryReport implements GetAppDailySummaryReportInterface
{
    /**
     * @throws EmptyReportingPeriodException
     * @throws MetricDefinitionMismatchException
     */
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel
    {
        $definition = $input->definition();
        $column = match ($definition->metric) {
            Metric::APPLICATION_USERS => 'application_users', Metric::REGISTERED_USERS => 'registered_users',
            default => throw new MetricDefinitionMismatchException('The requested metric is not an App metric.'),
        };
        $summaries = SummaryModel::query()
            ->where('measurements->' . $definition->metric->value . '->source', $definition->source)
            ->where('measurements->' . $definition->metric->value . '->metric', $definition->metric->value)
            ->where('measurements->' . $definition->metric->value . '->grain', $definition->grain)
            ->where('measurements->' . $definition->metric->value . '->version', $definition->version)
            ->where('day', '>=', (string) $input->period()->start())->where('day', '<', (string) $input->period()->endExclusive())
            ->whereNotNull($column);
        $revisionParts = [];
        $qualities = [];
        foreach ((clone $summaries)->get() as $row) {
            $metadata = $row->measurements[$definition->metric->value];
            $revisionParts[] = $row->id . ':' . $row->revision;
            $qualities[$row->day] = array_values(array_unique([...($qualities[$row->day] ?? []), ...$metadata['quality']]));
        }
        sort($revisionParts);
        $revision = $revisionParts === [] ? null : hash('sha256', implode("\0", $revisionParts));
        $denominatorExpression = 'NULL';
        $daily = (clone $summaries)->select('day')->selectRaw('SUM(' . $column . ') AS value, ' . $denominatorExpression . ' AS denominator, MAX(updated_at) AS updated_at')
            ->groupBy('day')->get()->keyBy('day');
        $days = [];
        foreach ($input->period()->days() as $day) {
            $values = $daily->get((string) $day);
            $days[] = [
                'day' => (string) $day,
                'availability' => $values !== null ? 'measured' : 'missing',
                'value' => $values->value ?? null,
                'quality' => $qualities[(string) $day] ?? [],
                'updatedAt' => $values?->updated_at?->toIso8601String(),
            ];
        }
        $last = $days[array_key_last($days) ?? throw new EmptyReportingPeriodException()];
        $total = $definition->metric->isSnapshot() ? $last['value'] : ($daily->isEmpty() ? null : $summaries->selectRaw('COALESCE(SUM(' . $column . '), 0) AS value')->first()?->value);

        return new AppDailySummaryReportReadModel($definition, $revision, $days, $total);
    }
}
