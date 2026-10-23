<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsRequest;
use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsResponse;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsRequest;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsResponse;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsRequest;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsResponse;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Source\Shared\Application\Exception\YouTubeRequestFailedException;

class YouTubeClient
{
    public function __construct(
        private readonly UriInterface $uri,
        private readonly string $apiKey,
        private readonly ClientInterface $client,
        private readonly PsrFactories $psrFactories,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /** @throws YouTubeRequestFailedException */
    public function searchVideoIds(SearchVideoIdsRequest $request): SearchVideoIdsResponse
    {
        $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);

        return new SearchVideoIdsResponse($this->send($request->toPsrRequest($baseRequest, $this->apiKey)));
    }

    /** @throws YouTubeRequestFailedException */
    public function searchRecentVideoIds(SearchRecentVideoIdsRequest $request): SearchRecentVideoIdsResponse
    {
        $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);

        return new SearchRecentVideoIdsResponse($this->send($request->toPsrRequest($baseRequest, $this->apiKey)));
    }

    /**
     * @throws YouTubeRequestFailedException
     * @throws JsonException
     */
    public function getVideoDetails(GetVideoDetailsRequest $request): GetVideoDetailsResponse
    {
        $allDetails = [];
        $chunks = array_chunk($request->videoIds(), 50);

        foreach ($chunks as $chunk) {
            $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);
            $psrRequest = $request->toPsrRequest($baseRequest, $this->apiKey, $chunk);

            $response = $this->send($psrRequest);
            $chunkResponse = new GetVideoDetailsResponse($response);
            if (array_diff($chunk, array_keys($chunkResponse->details())) !== []) {
                throw new YouTubeRequestFailedException();
            }
            $allDetails += $chunkResponse->details();
        }

        $items = array_values(array_map(
            static fn (array $detail): array => [
                'id' => $detail['id'],
                'snippet' => [
                    'title' => $detail['title'],
                    'publishedAt' => $detail['publishedAt'],
                    'thumbnails' => [
                        'high' => ['url' => $detail['thumbnailUrl']],
                        'default' => ['url' => $detail['thumbnailUrl']],
                    ],
                ],
                'contentDetails' => $detail['durationSeconds'] === null
                    ? []
                    : ['duration' => 'PT' . $detail['durationSeconds'] . 'S'],
                'statistics' => [
                    'viewCount' => (string) $detail['viewCount'],
                    'likeCount' => (string) $detail['likeCount'],
                ],
            ],
            $allDetails,
        ));

        $json = json_encode(['items' => $items], JSON_THROW_ON_ERROR);
        $stream = $this->psrFactories->getStreamFactory()->createStream($json);

        return new GetVideoDetailsResponse(
            $this->psrFactories->getResponseFactory()->createResponse()->withBody($stream),
        );
    }

    /** @throws YouTubeRequestFailedException */
    private function send(RequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface) {
            $this->logger->error('Video provider transport failed');

            throw new YouTubeRequestFailedException();
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->logger->error('Video provider request failed', ['status' => $response->getStatusCode()]);

            throw new YouTubeRequestFailedException();
        }

        try {
            $contents = (string) $response->getBody();
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new YouTubeRequestFailedException();
        }
        if (! is_array($data) || ! isset($data['items']) || ! is_array($data['items'])) {
            throw new YouTubeRequestFailedException();
        }

        return $response->withBody($this->psrFactories->getStreamFactory()->createStream($contents));
    }
}
