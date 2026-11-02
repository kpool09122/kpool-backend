<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\RunReport;

use Application\Http\Client\Foundation\Json\Decoder;
use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final readonly class RunReportResponse
{
    private RunReportParams $params;

    public function __construct(ResponseInterface $response, RunReportRequest $request)
    {
        try {
            $data = Decoder::decode($response->getBody()->getContents(), true);
        } catch (Throwable) {
            throw new GoogleAnalyticsRequestFailedException();
        }
        if (! is_array($data) || array_is_list($data)) {
            throw new GoogleAnalyticsRequestFailedException();
        }
        /** @var array<string, mixed> $data */
        $this->params = RunReportParams::fromArray($data, $request);
    }

    public function params(): RunReportParams
    {
        return $this->params;
    }

    /** @return list<AnalyticsReportRow> */
    public function rows(): array
    {
        return $this->params->rows();
    }

    public function rowCount(): int
    {
        return $this->params->rowCount();
    }
}
