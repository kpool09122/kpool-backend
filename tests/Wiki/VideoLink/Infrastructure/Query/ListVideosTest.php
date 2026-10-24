<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutput;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListVideosTest extends TestCase
{
    public function testReturnsManualAndAutomaticVideosForRequestedPublishedWikiInStableOrder(): void
    {
        $id = StrTestHelper::generateUuid();
        $other = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'group', ['slug' => 'gr-test', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create($other, 'group', ['slug' => 'gr-test', 'language' => 'en', 'published_at' => '2026-01-01']);
        $ids = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        sort($ids);
        foreach (['youtube_auto_view_count', 'youtube_auto_like_count', 'youtube_auto_recent_popular'] as $i => $usage) {
            CreateVideoLink::create($ids[$i], ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => $usage, 'created_at' => '2026-10-01 00:00:00']);
        }
        $manualId = StrTestHelper::generateUuid();
        CreateVideoLink::create($manualId, ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => 'music_video', 'duration_seconds' => 253, 'created_at' => '2026-10-02 00:00:00']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $other, 'resource_type' => 'group', 'video_usage' => 'youtube_auto_view_count']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id, 'resource_type' => 'talent', 'video_usage' => 'youtube_auto_view_count']);
        $payload = $this->process('gr-test', Language::JAPANESE, ResourceType::GROUP);
        $this->assertSame([$manualId, ...$ids], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertSame(['videoLinkIdentifier', 'url', 'title', 'thumbnailUrl', 'publishedAt', 'videoUsage', 'durationSeconds'], array_keys($payload['videos'][0]));
        $this->assertNull($payload['videos'][0]['thumbnailUrl']);
        $this->assertNull($payload['videos'][0]['publishedAt']);
        $this->assertSame('Test Video', $payload['videos'][0]['title']);
        $this->assertSame(253, $payload['videos'][0]['durationSeconds']);
        $this->assertNull($payload['videos'][1]['durationSeconds']);
    }

    /** @param list<?int> $expectedDurations */
    #[DataProvider('durationRanges')]
    public function testFiltersDurationWithInclusiveBounds(?int $minimum, ?int $maximum, array $expectedDurations): void
    {
        $wikiId = StrTestHelper::generateUuid();
        CreateWiki::create($wikiId, 'group', ['slug' => 'gr-duration', 'language' => 'ja', 'published_at' => '2026-01-01']);
        foreach ([null, 0, 599, 600, 1200, 2400, 3600] as $duration) {
            CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $wikiId, 'resource_type' => 'group', 'duration_seconds' => $duration]);
        }
        $output = new ListVideosOutput();
        $input = new ListVideosInput(new Slug('gr-duration'), Language::JAPANESE, ResourceType::GROUP, $minimum, $maximum);
        $this->app()->make(ListVideosInterface::class)->process($input, $output);
        $durations = array_column($output->toArray()['videos'], 'durationSeconds');
        $this->assertEqualsCanonicalizing($expectedDurations, $durations);
    }

    /** @return list<array{?int, ?int, list<?int>}> */
    public static function durationRanges(): array
    {
        return [
            [null, null, [null, 0, 599, 600, 1200, 2400, 3600]],
            [600, null, [600, 1200, 2400, 3600]],
            [null, 600, [0, 599, 600]],
            [600, 1200, [600, 1200]],
            [2400, null, [2400, 3600]],
            [600, 600, [600]],
            [0, null, [0, 599, 600, 1200, 2400, 3600]],
            [null, 0, [0]],
            [1, 1, []],
        ];
    }

    public function testReturnsManuallyCreatedAgencyVideos(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'agency', ['slug' => 'ag-test','published_at' => '2026-01-01']);
        $videoId = StrTestHelper::generateUuid();
        CreateVideoLink::create($videoId, ['wiki_id' => $id, 'resource_type' => 'agency', 'video_usage' => 'music_video']);
        $payload = $this->process('ag-test', Language::KOREAN, ResourceType::AGENCY);
        $this->assertSame([$videoId], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertSame('music_video', $payload['videos'][0]['videoUsage']);
    }

    #[DataProvider('resourceTypes')]
    public function testResolvesAllPublishedResourceTypes(string $type, string $slug): void
    {
        CreateWiki::create(StrTestHelper::generateUuid(), $type, ['slug' => $slug, 'published_at' => '2026-01-01']);
        $this->assertSame(['videos' => []], $this->process($slug, Language::KOREAN, ResourceType::from($type)));
    }

    /** @return list<array{string,string}> */
    public static function resourceTypes(): array
    {
        return [['agency', 'ag-test'], ['group', 'gr-test'], ['song', 'sg-test'], ['talent', 'tl-test']];
    }

    #[DataProvider('missingWikis')]
    public function testRejectsMissingUnpublishedOrDifferentScope(string $slug, Language $language, ResourceType $type): void
    {
        CreateWiki::create(StrTestHelper::generateUuid(), 'group', ['slug' => 'gr-test', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create(StrTestHelper::generateUuid(), 'group', ['slug' => 'gr-unpublished', 'language' => 'ja']);
        $this->expectException(WikiNotFoundException::class);
        $this->process($slug, $language, $type);
    }

    /** @return list<array{string,Language,ResourceType}> */
    public static function missingWikis(): array
    {
        return [['gr-missing', Language::JAPANESE, ResourceType::GROUP], ['gr-test', Language::ENGLISH, ResourceType::GROUP], ['gr-test', Language::JAPANESE, ResourceType::TALENT], ['gr-unpublished', Language::JAPANESE, ResourceType::GROUP]];
    }

    /** @return array{videos: list<array<string, mixed>>} */
    private function process(string $slug, Language $language, ResourceType $type): array
    {
        $output = new ListVideosOutput();
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug($slug), $language, $type), $output);

        return $output->toArray();
    }
}
