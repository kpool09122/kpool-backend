<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\RunReport;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;

final readonly class RunReportParams
{
    /** @param list<AnalyticsReportRow> $rows */
    private function __construct(private array $rows, private int $rowCount, private string $timeZone)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, RunReportRequest $request): self
    {
        $metadata = $data['metadata'] ?? null;
        if (! is_array($metadata) || ! is_string($metadata['timeZone'] ?? null) || $metadata['timeZone'] === ''
            || ($metadata['dataLossFromOtherRow'] ?? false) !== false || ($metadata['subjectToThresholding'] ?? false) !== false
            || ! empty($metadata['samplingMetadatas']) || ! empty($metadata['schemaRestrictionResponse']) || ! empty($metadata['emptyReason'])) {
            throw new GoogleAnalyticsRequestFailedException('Analytics must return complete, unsampled data.');
        }
        self::assertHeaders($data['dimensionHeaders'] ?? [], $request->dimensions());
        self::assertHeaders($data['metricHeaders'] ?? null, $request->metrics());
        $rowCount = $data['rowCount'] ?? 0;
        $rawRows = $data['rows'] ?? [];
        if (! is_int($rowCount) || $rowCount < 0 || ! is_array($rawRows) || ! array_is_list($rawRows)) {
            throw new GoogleAnalyticsRequestFailedException();
        }
        $rows = [];
        foreach ($rawRows as $row) {
            if (! is_array($row)) {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $dimensions = self::values($row['dimensionValues'] ?? [], count($request->dimensions()));
            $metrics = self::values($row['metricValues'] ?? null, count($request->metrics()));
            $dimensionValues = array_combine($request->dimensions(), $dimensions);
            if ((isset($dimensionValues['date']) && $dimensionValues['date'] !== str_replace('-', '', $request->day())) || in_array('(other)', $dimensions, true)) {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $rows[] = new AnalyticsReportRow($dimensionValues, array_combine($request->metrics(), $metrics));
        }
        if (count($rows) > $request->limit() || $request->offset() + count($rows) > $rowCount) {
            throw new GoogleAnalyticsRequestFailedException();
        }

        return new self($rows, $rowCount, $metadata['timeZone']);
    }

    /** @return list<AnalyticsReportRow> */
    public function rows(): array
    {
        return $this->rows;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }

    public function timeZone(): string
    {
        return $this->timeZone;
    }

    /** @param list<string> $expected */
    private static function assertHeaders(mixed $items, array $expected): void
    {
        if (! is_array($items) || ! array_is_list($items)) {
            throw new GoogleAnalyticsRequestFailedException();
        }
        $names = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['name'] ?? null)) {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $names[] = $item['name'];
        }
        if ($names !== $expected) {
            throw new GoogleAnalyticsRequestFailedException();
        }
    }

    /** @return list<string> */
    private static function values(mixed $items, int $expected): array
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) !== $expected) {
            throw new GoogleAnalyticsRequestFailedException();
        }
        $values = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['value'] ?? null)) {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $values[] = $item['value'];
        }

        return $values;
    }
}
