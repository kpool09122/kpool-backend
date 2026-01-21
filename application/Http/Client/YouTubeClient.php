<?php

declare(strict_types=1);

namespace Application\Http\Client;

use Carbon\CarbonImmutable;
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

    /**
     * @return string[]
     */
    public function searchVideoIds(string $keyword, string $order, int $maxResults): array
    {
        $response = Http::get(self::BASE_URL.'/search', [
            'key' => $this->apiKey,
            'q' => $keyword,
            'type' => 'video',
            'part' => 'id',
            'order' => $order,
            'maxResults' => $maxResults,
        ]);

        if (! $response->successful()) {
            Log::error('YouTube search API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $data = $response->json();

        return array_map(
            static fn (array $item): string => $item['id']['videoId'],
            $data['items'] ?? [],
        );
    }

    /**
     * @return string[]
     */
    public function searchRecentVideoIds(string $keyword, int $maxResults, CarbonImmutable $publishedAfter): array
    {
        $response = Http::get(self::BASE_URL.'/search', [
            'key' => $this->apiKey,
            'q' => $keyword,
            'type' => 'video',
            'part' => 'id',
            'order' => 'viewCount',
            'publishedAfter' => $publishedAfter->format('c'),
            'maxResults' => $maxResults,
        ]);

        if (! $response->successful()) {
            Log::error('YouTube recent search API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $data = $response->json();

        return array_map(
            static fn (array $item): string => $item['id']['videoId'],
            $data['items'] ?? [],
        );
    }

    /**
     * @param  string[]  $videoIds
     * @return array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}>
     */
    public function getVideoDetails(array $videoIds): array
    {
        $details = [];
        $chunks = array_chunk($videoIds, 50);

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

        return $details;
    }
}
