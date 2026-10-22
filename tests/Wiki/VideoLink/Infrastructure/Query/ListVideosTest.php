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
    public function testReturnsOnlyRequestedPublishedWikiAutoVideosInStableOrder(): void
    {
        $id = StrTestHelper::generateUuid();
        $other = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'group', ['slug' => 'gr-test', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create($other, 'group', ['slug' => 'gr-test', 'language' => 'en', 'published_at' => '2026-01-01']);
        $ids = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        sort($ids);
        foreach (['youtube_auto_view_count', 'youtube_auto_like_count', 'youtube_auto_recent_popular'] as $i => $usage) {
            CreateVideoLink::create($ids[$i], ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => $usage, 'display_order' => $i === 2 ? 1 : 2]);
        }
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => 'music_video']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $other, 'resource_type' => 'group', 'video_usage' => 'youtube_auto_view_count']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id, 'resource_type' => 'talent', 'video_usage' => 'youtube_auto_view_count']);
        $payload = $this->process('gr-test', Language::JAPANESE, ResourceType::GROUP);
        $this->assertSame([$ids[2], $ids[0], $ids[1]], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertSame(['videoLinkIdentifier', 'url', 'title', 'thumbnailUrl', 'publishedAt', 'displayOrder', 'videoUsage'], array_keys($payload['videos'][0]));
        $this->assertNull($payload['videos'][0]['thumbnailUrl']);
        $this->assertNull($payload['videos'][0]['publishedAt']);
        $this->assertSame('Test Video', $payload['videos'][0]['title']);
    }

    public function testUnsupportedAgencyReturnsEmptyEvenWithStoredVideos(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'agency', ['slug' => 'ag-test','published_at' => '2026-01-01']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id,'resource_type' => 'agency','video_usage' => 'youtube_auto_view_count']);
        $this->assertSame(['videos' => []], $this->process('ag-test', Language::KOREAN, ResourceType::AGENCY));
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
