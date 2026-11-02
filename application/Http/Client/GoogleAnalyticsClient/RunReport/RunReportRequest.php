<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\RunReport;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class RunReportRequest
{
    /** @param non-empty-list<string> $metrics
     * @param list<string> $dimensions
     */
    public function __construct(private string $day, private array $metrics, private array $dimensions = [], private ?FilterExpression $dimensionFilter = null, private int $offset = 0, private int $limit = 10000)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $day) || $offset < 0 || $limit < 1 || $limit > 10000 || $metrics === []
            || count(array_unique($metrics)) !== count($metrics) || count(array_unique($dimensions)) !== count($dimensions)) {
            throw new InvalidArgumentException('Invalid analytics report range or fields.');
        }
        foreach ([...$metrics, ...$dimensions] as $name) {
            if ($name === '') {
                throw new InvalidArgumentException('Analytics field names must not be empty.');
            }
        }
    }

    public function day(): string
    {
        return $this->day;
    }

    /** @return non-empty-list<string> */
    public function metrics(): array
    {
        return $this->metrics;
    }

    /** @return list<string> */
    public function dimensions(): array
    {
        return $this->dimensions;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function toPsrRequest(RequestInterface $request, StreamFactoryInterface $streamFactory): RequestInterface
    {
        $body = [
            'dateRanges' => [['startDate' => $this->day, 'endDate' => $this->day]],
            'dimensions' => array_map(static fn (string $name): array => ['name' => $name], $this->dimensions),
            'metrics' => array_map(static fn (string $name): array => ['name' => $name], $this->metrics),
            'orderBys' => array_map(static fn (string $name): array => ['dimension' => ['dimensionName' => $name]], $this->dimensions),
            'offset' => (string) $this->offset, 'limit' => (string) $this->limit,
            'keepEmptyRows' => true, 'returnPropertyQuota' => true,
        ];
        if ($this->dimensionFilter !== null) {
            $body['dimensionFilter'] = $this->dimensionFilter->toArray();
        }

        return $request->withMethod('POST')->withUri($request->getUri()->withPath($request->getUri()->getPath() . ':runReport'))
            ->withHeader('Content-Type', 'application/json')->withBody($streamFactory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
    }
}
