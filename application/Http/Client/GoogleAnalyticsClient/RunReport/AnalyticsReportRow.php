<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\RunReport;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;

readonly class AnalyticsReportRow
{
    /** @param array<string, string> $dimensions
     * @param array<string, string> $metrics
     */
    public function __construct(private array $dimensions, private array $metrics)
    {
    }

    public function dimension(string $name): string
    {
        return $this->dimensions[$name] ?? throw new GoogleAnalyticsRequestFailedException('Requested Analytics dimension is missing.');
    }

    public function metric(string $name): string
    {
        return $this->metrics[$name] ?? throw new GoogleAnalyticsRequestFailedException('Requested Analytics metric is missing.');
    }

    /** @return array<string, string> */
    public function dimensions(): array
    {
        return $this->dimensions;
    }

    /** @return array<string, string> */
    public function metrics(): array
    {
        return $this->metrics;
    }
}
