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
use Illuminate\Support\Facades\Log;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class YouTubeClient
{
    public function __construct(
        private readonly UriInterface $uri,
        private readonly string $apiKey,
        private readonly ClientInterface $client,
        private readonly PsrFactories $psrFactories,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function searchVideoIds(SearchVideoIdsRequest $request): SearchVideoIdsResponse
    {
        $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);
        $psrRequest = $request->toPsrRequest($baseRequest, $this->apiKey);

        try {
            $response = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            Log::error('YouTube search API failed', [
                'message' => $e->getMessage(),
            ]);

            return new SearchVideoIdsResponse($this->createEmptyJsonResponse());
        }

        if ($response->getStatusCode() >= 400) {
            Log::error('YouTube search API failed', [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);

            return new SearchVideoIdsResponse($this->createEmptyJsonResponse());
        }

        return new SearchVideoIdsResponse($response);
    }

    public function searchRecentVideoIds(SearchRecentVideoIdsRequest $request): SearchRecentVideoIdsResponse
    {
        $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);
        $psrRequest = $request->toPsrRequest($baseRequest, $this->apiKey);

        try {
            $response = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            Log::error('YouTube recent search API failed', [
                'message' => $e->getMessage(),
            ]);

            return new SearchRecentVideoIdsResponse($this->createEmptyJsonResponse());
        }

        if ($response->getStatusCode() >= 400) {
            Log::error('YouTube recent search API failed', [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ]);

            return new SearchRecentVideoIdsResponse($this->createEmptyJsonResponse());
        }

        return new SearchRecentVideoIdsResponse($response);
    }

    /**
     * @throws JsonException
     */
    public function getVideoDetails(GetVideoDetailsRequest $request): GetVideoDetailsResponse
    {
        $allDetails = [];
        $chunks = array_chunk($request->videoIds(), 50);

        foreach ($chunks as $chunk) {
            $baseRequest = $this->psrFactories->getRequestFactory()->createRequest('GET', $this->uri);
            $psrRequest = $request->toPsrRequest($baseRequest, $this->apiKey, $chunk);

            try {
                $response = $this->client->sendRequest($psrRequest);
            } catch (ClientExceptionInterface $e) {
                Log::error('YouTube videos API failed', [
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            if ($response->getStatusCode() >= 400) {
                Log::error('YouTube videos API failed', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents(),
                ]);

                continue;
            }

            $chunkResponse = new GetVideoDetailsResponse($response);
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

    private function createEmptyJsonResponse(): ResponseInterface
    {
        $stream = $this->psrFactories->getStreamFactory()->createStream('{"items":[]}');

        return $this->psrFactories->getResponseFactory()->createResponse()->withBody($stream);
    }
}
