<?php

declare(strict_types=1);

namespace Tests\Application\Http\Client\YouTubeClient;

use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsRequest;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsRequest;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsRequest;
use Application\Http\Client\YouTubeClient\YouTubeClient;
use Carbon\CarbonImmutable;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Source\Shared\Application\Exception\YouTubeRequestFailedException;
use Tests\Helper\YouTubeClientTestHelper;
use Tests\TestCase;

class YouTubeClientTest extends TestCase
{
    #[DataProvider('searchResultTypes')]
    public function testSearchIgnoresResultsWithoutVideoIds(bool $recent, bool $withVideos): void
    {
        $items = [
            ['id' => ['channelId' => 'channel1']],
            ['id' => ['playlistId' => 'playlist1']],
            ['id' => []],
            [],
            ['id' => ['videoId' => null]],
            ['id' => ['videoId' => '']],
        ];
        if ($withVideos) {
            array_unshift($items, ['id' => ['videoId' => 'video1']]);
            $items[] = ['id' => ['videoId' => 'video2']];
        }

        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('sendRequest')->once()->andReturn(
            new Response(200, [], json_encode(['items' => $items], JSON_THROW_ON_ERROR)),
        );
        $client = $this->client($http);
        $response = $recent
            ? $client->searchRecentVideoIds(new SearchRecentVideoIdsRequest('TWICE', 30, new CarbonImmutable()))
            : $client->searchVideoIds(new SearchVideoIdsRequest('TWICE', 'relevance', 30));

        self::assertSame($withVideos ? ['video1', 'video2'] : [], $response->videoIds());
    }

    /** @return array<string, array{bool, bool}> */
    public static function searchResultTypes(): array
    {
        return [
            'regular mixed results' => [false, true],
            'recent mixed results' => [true, true],
            'regular without videos' => [false, false],
            'recent without videos' => [true, false],
        ];
    }

    #[DataProvider('searchFailures')]
    public function testSearchFailuresAreNotEmptySuccesses(bool $recent, bool $transport): void
    {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $call = $http->shouldReceive('sendRequest')->once();
        if ($transport) {
            $call->andThrow(new ConnectException('secret-api-key', new Request('GET', 'https://example.com')));
        } else {
            $call->andReturn(new Response(503, [], 'secret-response'));
        }
        $client = $this->client($http);
        $this->expectException(YouTubeRequestFailedException::class);
        if ($recent) {
            $client->searchRecentVideoIds(new SearchRecentVideoIdsRequest('test', 30, new CarbonImmutable()));
        } else {
            $client->searchVideoIds(new SearchVideoIdsRequest('test', 'viewCount', 30));
        }
    }

    /** @return list<array{bool,bool}> */
    public static function searchFailures(): array
    {
        return [[false,false],[true,false],[false,true],[true,true]];
    }

    public function testPartialChunkFailureAbortsWholeDetailsRequest(): void
    {
        $ids = array_map(static fn (int $i): string => 'video' . $i, range(1, 51));
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('sendRequest')->once()->ordered()->andReturn(new Response(200, [], json_encode(['items' => array_map(static fn (string $id): array => ['id' => $id, 'snippet' => ['title' => 'test','publishedAt' => '2026-01-01T00:00:00Z','thumbnails' => ['default' => ['url' => 'https://example.com/t.jpg']]],'statistics' => []], array_slice($ids, 0, 50))], JSON_THROW_ON_ERROR)));
        $http->shouldReceive('sendRequest')->once()->ordered()->andReturn(new Response(503));
        $this->expectException(YouTubeRequestFailedException::class);
        $this->client($http)->getVideoDetails(new GetVideoDetailsRequest($ids));
    }

    public function testMissingDetailIsFailureRatherThanPartialSuccess(): void
    {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('sendRequest')->once()->andReturn(new Response(200, [], '{"items":[]}'));
        $this->expectException(YouTubeRequestFailedException::class);
        $this->client($http)->getVideoDetails(new GetVideoDetailsRequest(['missing']));
    }

    public function testSuccessfulZeroSearchIsEmpty(): void
    {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('sendRequest')->once()->andReturn(new Response(200, [], '{"items":[]}'));
        $this->assertSame([], $this->client($http)->searchVideoIds(new SearchVideoIdsRequest('test', 'viewCount', 30))->videoIds());
    }

    private function client(ClientInterface $http): YouTubeClient
    {
        return YouTubeClientTestHelper::create($http);
    }
}
