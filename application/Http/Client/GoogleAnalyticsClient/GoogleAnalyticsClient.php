<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportResponse;
use Google\Auth\FetchAuthTokenInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GoogleAnalyticsClient implements GoogleAnalyticsClientInterface
{
    public function __construct(
        private readonly string $propertyIdentifier,
        private readonly FetchAuthTokenInterface $credentials,
        private readonly ClientInterface $client,
        private readonly PsrFactories $psrFactories,
    ) {
        if (! preg_match('/^[0-9]+$/D', $propertyIdentifier)) {
            throw new GoogleAnalyticsRequestFailedException('Analytics property must be configured.');
        }
    }

    public function runReport(RunReportRequest $request): RunReportResponse
    {
        $httpRequest = $request->toPsrRequest($this->baseRequest(), $this->psrFactories->getStreamFactory());

        return new RunReportResponse($this->send($httpRequest), $request);
    }

    private function baseRequest(): RequestInterface
    {
        return $this->psrFactories->getRequestFactory()->createRequest('GET', 'https://analyticsdata.googleapis.com/v1beta/properties/' . $this->propertyIdentifier);
    }

    private function send(RequestInterface $request): ResponseInterface
    {
        try {
            $token = $this->credentials->getLastReceivedToken();
            if ($token === null || ! is_int($token['expires_at'] ?? null) || $token['expires_at'] <= time() + 30) {
                $token = $this->credentials->fetchAuthToken(fn (RequestInterface $request): ResponseInterface => $this->client->sendRequest($request));
            }
            if (! is_string($token['access_token'] ?? null) || $token['access_token'] === '') {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $response = $this->client->sendRequest($request->withHeader('Authorization', 'Bearer ' . $token['access_token']));
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new GoogleAnalyticsRequestFailedException();
            }

            return $response;
        } catch (Throwable) {
            // Never attach response bodies, OAuth tokens or credential paths to exceptions.
            throw new GoogleAnalyticsRequestFailedException();
        }
    }
}
