<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Http\Client\YouTubeClient\YouTubeClient;
use Application\Jobs\Wiki\CollectVideoLinksJob;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;
use Source\Shared\Application\Exception\YouTubeRequestFailedException;
use Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkCollectionFailedException;
use Source\Wiki\VideoLink\Domain\Exception\VideoSearchFailedException;
use Source\Wiki\VideoLink\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLink\Domain\ValueObject\YouTubeVideoInfo;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateVideoLinkCollectionStatus;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\Helper\YouTubeClientTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class CollectVideoLinksJobIntegrationTest extends TestCase
{
    private string $wikiId = '';
    private string $oldVideoId = '';
    private string $statusId = '';

    protected function setUp(): void
    {
        parent::setUp();
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $this->app()->instance(YouTubeClient::class, YouTubeClientTestHelper::create($http));
        $this->wikiId = StrTestHelper::generateUuid();
        $this->oldVideoId = StrTestHelper::generateUuid();
        $this->statusId = StrTestHelper::generateUuid();
        CreateWiki::create($this->wikiId, 'group', ['published_at' => '2026-01-01']);
        CreateVideoLink::create($this->oldVideoId, ['wiki_id' => $this->wikiId,'resource_type' => 'group','video_usage' => 'youtube_auto_view_count']);
        CreateVideoLinkCollectionStatus::create($this->statusId, ['wiki_id' => $this->wikiId,'resource_type' => 'group','last_collected_at' => '2020-01-01 00:00:00']);
    }

    public function testRealSearchCollectsRelatedMultilingualTitleAndExcludesUnrelatedVideo(): void
    {
        CreateWiki::create(StrTestHelper::generateUuid(), 'talent', ['language' => 'en'], [
            'name' => 'MOMO',
            'group_identifiers' => json_encode([$this->wikiId], JSON_THROW_ON_ERROR),
        ]);
        $searchBody = json_encode(['items' => [
            ['id' => ['videoId' => 'abcdefghijk']],
            ['id' => ['videoId' => '01234567890']],
        ]], JSON_THROW_ON_ERROR);
        $items = [];
        foreach (['abcdefghijk' => 'momo fancam', '01234567890' => 'Unrelated video'] as $id => $title) {
            $items[] = [
                'id' => $id,
                'snippet' => [
                    'title' => $title,
                    'publishedAt' => '2026-01-01T00:00:00Z',
                    'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg']],
                ],
                'statistics' => ['viewCount' => '100000', 'likeCount' => '10000'],
                'contentDetails' => ['duration' => 'PT3M'],
            ];
        }
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        foreach ([$searchBody, $searchBody, json_encode(['items' => $items], JSON_THROW_ON_ERROR)] as $body) {
            $http->shouldReceive('sendRequest')->once()->ordered()->andReturn(new Response(200, [], $body));
        }
        $this->app()->instance(YouTubeClient::class, YouTubeClientTestHelper::create($http));

        $this->collect();

        self::assertSame(['momo fancam'], DB::table('wiki_video_links')->where('wiki_id', $this->wikiId)->pluck('title')->all());
        self::assertNotNull(DB::table('video_link_collection_statuses')->where('id', $this->statusId)->value('last_collected_at'));
    }

    /** @param list<array{int,string}> $responses */
    #[DataProvider('providerFailures')]
    public function testRealProviderFailurePreservesStoredCollection(array $responses): void
    {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        foreach ($responses as [$status, $body]) {
            $http->shouldReceive('sendRequest')->once()->ordered()->andReturn(new Response($status, [], $body));
        }
        $this->app()->instance(YouTubeClient::class, YouTubeClientTestHelper::create($http));
        $beforeVideos = DB::table('wiki_video_links')->get()->toArray();
        $beforeStatuses = DB::table('video_link_collection_statuses')->get()->toArray();

        try {
            $this->collect();
            $this->fail('A total or partial provider failure must propagate');
        } catch (VideoSearchFailedException $e) {
            $cause = $e->getPrevious();
            $this->assertInstanceOf(YouTubeRequestFailedException::class, $cause);
            $this->assertSame('Video provider request failed.', $cause->getMessage());
            $this->assertSame($cause->getMessage(), $e->getMessage());
            $this->assertNull($cause->getPrevious(), 'Raw provider exceptions must not enter the exception chain');
            $this->assertEquals($beforeVideos, DB::table('wiki_video_links')->get()->toArray());
            $this->assertEquals($beforeStatuses, DB::table('video_link_collection_statuses')->get()->toArray());
        }
    }

    /** @return array<string,array{list<array{int,string}>}> */
    public static function providerFailures(): array
    {
        $found = [200, '{"items":[{"id":{"videoId":"first"}}]}'];
        $empty = [200, '{"items":[]}'];

        return [
            'total search failure' => [[[503, 'provider response']]],
            'partial search failure' => [[$found, [503, 'provider response']]],
            'details failure' => [[$found, $empty, [503, 'provider response']]],
            'missing details' => [[$found, $empty, $empty]],
            'malformed JSON' => [[[200, '{']]],
            'missing items' => [[[200, '{}']]],
        ];
    }

    public function testSearchFailurePreservesVideosAndTimestamp(): void
    {
        $search = Mockery::mock(YouTubeSearchServiceInterface::class);
        $search->shouldReceive('searchVideos')->once()->andThrow(new VideoSearchFailedException());
        $this->app()->instance(YouTubeSearchServiceInterface::class, $search);

        try {
            $this->collect();
            $this->fail('Search failure must propagate');
        } catch (VideoSearchFailedException) {
            $this->assertOldState();
        }
    }

    public function testSuccessfulZeroReplacesAutoVideosButPreservesManualAndOtherWiki(): void
    {
        $manualId = StrTestHelper::generateUuid();
        $otherId = StrTestHelper::generateUuid();
        CreateVideoLink::create($manualId, ['wiki_id' => $this->wikiId, 'resource_type' => 'group']);
        CreateVideoLink::create($otherId, ['video_usage' => 'youtube_auto_view_count']);
        $this->searchReturns([]);
        $this->collect();
        $this->assertFalse(DB::table('wiki_video_links')->where('id', $this->oldVideoId)->exists());
        $this->assertTrue(DB::table('wiki_video_links')->where('id', $manualId)->exists());
        $this->assertTrue(DB::table('wiki_video_links')->where('id', $otherId)->exists());
        $this->assertNotSame('2020-01-01 00:00:00', DB::table('video_link_collection_statuses')->where('id', $this->statusId)->value('last_collected_at'));
    }

    public function testFailureAfterFirstInsertRollsBackDeletionInsertAndTimestamp(): void
    {
        DB::statement("ALTER TABLE wiki_video_links ADD CONSTRAINT test_video_failure CHECK (title <> 'fail')");
        $firstInsertObserved = false;
        DB::listen(static function (QueryExecuted $event) use (&$firstInsertObserved): void {
            if (str_starts_with($event->sql, 'insert into "wiki_video_links"') && in_array('first', $event->bindings, true)) {
                $firstInsertObserved = true;
            }
        });
        $this->searchReturns([$this->video('first'),$this->video('fail')]);

        try {
            $this->collect();
            $this->fail('Second insert must fail');
        } catch (VideoLinkCollectionFailedException) {
            $this->assertTrue($firstInsertObserved);
            $this->assertOldState();
            $this->assertSame(1, DB::table('wiki_video_links')->where('wiki_id', $this->wikiId)->count());
        }
    }

    public function testStatusSaveFailureRollsBackVideoReplacement(): void
    {
        DB::statement("ALTER TABLE video_link_collection_statuses ADD CONSTRAINT test_status_failure CHECK (last_collected_at = '2020-01-01 00:00:00')");
        $this->searchReturns([$this->video('first')]);

        try {
            $this->collect();
            $this->fail('Status update must fail');
        } catch (VideoLinkCollectionFailedException) {
            $this->assertOldState();
            $this->assertSame(1, DB::table('wiki_video_links')->where('wiki_id', $this->wikiId)->count());
        }
    }

    /** @param list<YouTubeVideoInfo> $videos */
    private function searchReturns(array $videos): void
    {
        $level = DB::transactionLevel();
        $search = Mockery::mock(YouTubeSearchServiceInterface::class);
        $search->shouldReceive('searchVideos')->once()->andReturnUsing(function () use ($videos, $level): array {
            $this->assertSame($level + 1, DB::transactionLevel(), 'Collection must run in the job transaction');

            return $videos;
        });
        $this->app()->instance(YouTubeSearchServiceInterface::class, $search);
    }

    private function video(string $title): YouTubeVideoInfo
    {
        return new YouTubeVideoInfo($title, $title, 'https://www.youtube.com/watch?v='.$title, 'https://i.ytimg.com/'.$title.'.jpg', VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, new DateTimeImmutable('2026-01-01'), durationSeconds: null);
    }

    /**
     * @throws VideoSearchFailedException
     * @throws VideoLinkCollectionFailedException
     */
    private function collect(): void
    {
        (new CollectVideoLinksJob())->handle(
            $this->app()->make(CollectVideoLinksInterface::class),
            $this->app()->make(YouTubeClient::class),
        );
    }

    private function assertOldState(): void
    {
        $this->assertTrue(DB::table('wiki_video_links')->where('id', $this->oldVideoId)->exists());
        $this->assertSame('2020-01-01 00:00:00', DB::table('video_link_collection_statuses')->where('id', $this->statusId)->value('last_collected_at'));
    }
}
