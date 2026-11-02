<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportResponse;

interface GoogleAnalyticsClientInterface
{
    /** @throws GoogleAnalyticsRequestFailedException */
    public function runReport(RunReportRequest $request): RunReportResponse;
}
