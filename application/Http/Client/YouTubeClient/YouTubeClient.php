<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient;

use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsRequest;
use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsResponse;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsRequest;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsResponse;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsRequest;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeClient
{
    private const string BASE_URL = 'https://www.googleapis.com/youtube/v3';

    public function __construct(
        private readonly string $apiKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function searchVideoIds(SearchVideoIdsRequest $request): SearchVideoIdsResponse
    {
        $response = Http::get(self::BASE_URL.'/search', [
            'key' => $this->apiKey,
            'q' => $request->keyword(),
            'type' => 'video',
            'part' => 'id',
            'order' => $request->order(),
            'maxResults' => $request->maxResults(),
        ]);

        if (! $response->successful()) {
            Log::error('YouTube search API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return new SearchVideoIdsResponse(videoIds: []);
        }

        $data = $response->json();

        $videoIds = array_map(
            static fn (array $item): string => $item['id']['videoId'],
            $data['items'] ?? [],
        );

        return new SearchVideoIdsResponse(videoIds: $videoIds);
    }

    public function searchRecentVideoIds(SearchRecentVideoIdsRequest $request): SearchRecentVideoIdsResponse
    {
        $response = Http::get(self::BASE_URL.'/search', [
            'key' => $this->apiKey,
            'q' => $request->keyword(),
            'type' => 'video',
            'part' => 'id',
            'order' => 'viewCount',
            'publishedAfter' => $request->publishedAfter()->format('c'),
            'maxResults' => $request->maxResults(),
        ]);

        if (! $response->successful()) {
            Log::error('YouTube recent search API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return new SearchRecentVideoIdsResponse(videoIds: []);
        }

        $data = $response->json();

        $videoIds = array_map(
            static fn (array $item): string => $item['id']['videoId'],
            $data['items'] ?? [],
        );

        return new SearchRecentVideoIdsResponse(videoIds: $videoIds);
    }

    public function getVideoDetails(GetVideoDetailsRequest $request): GetVideoDetailsResponse
    {
        $details = [];
        $chunks = array_chunk($request->videoIds(), 50);

        foreach ($chunks as $chunk) {
            $response = Http::get(self::BASE_URL.'/videos', [
                'key' => $this->apiKey,
                'id' => implode(',', $chunk),
                'part' => 'snippet,statistics',
            ]);

            if (! $response->successful()) {
                Log::error('YouTube videos API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                continue;
            }

            $data = $response->json();

            foreach ($data['items'] ?? [] as $item) {
                $details[$item['id']] = [
                    'id' => $item['id'],
                    'title' => $item['snippet']['title'],
                    'publishedAt' => $item['snippet']['publishedAt'],
                    'thumbnailUrl' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                    'viewCount' => (int) ($item['statistics']['viewCount'] ?? 0),
                    'likeCount' => (int) ($item['statistics']['likeCount'] ?? 0),
                ];
            }
        }

        return new GetVideoDetailsResponse(details: $details);
    }
}
