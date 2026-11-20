<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListPopularVideos\ListPopularVideosInterface;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListPopularVideosTest extends TestCase
{
    public function testRanksOrdinaryLikesAcrossLanguagesAndExcludesIneligibleWikis(): void
    {
        $wiki = StrTestHelper::generateUuid();
        $foreignWiki = StrTestHelper::generateUuid();
        $unpublishedWiki = StrTestHelper::generateUuid();
        $agency = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create($foreignWiki, 'talent', ['language' => 'en', 'published_at' => '2026-01-01']);
        CreateWiki::create($unpublishedWiki, 'talent', ['language' => 'ja', 'slug' => 'tl-unpublished']);
        CreateWiki::create($agency, 'agency', ['language' => 'ja', 'published_at' => '2026-01-01']);
        $first = $this->video($wiki, 3);
        $second = $this->video($wiki, 1);
        for ($i = 0; $i < 4; ++$i) {
            $this->like($second, true);
        }
        $officialOnly = $this->video($wiki, 0);
        $this->like($officialOnly, true);
        $unliked = $this->video($wiki, 0);
        $foreign = $this->video($foreignWiki, 5);
        $this->video($unpublishedWiki, 5);
        $this->video($agency, 5, 'agency');

        $videos = app(ListPopularVideosInterface::class)->process();
        $zeroLikeIds = [$officialOnly, $unliked];
        sort($zeroLikeIds);
        self::assertSame([$foreign, $first, $second, ...$zeroLikeIds], array_map(static fn ($video): string => $video->videoLinkIdentifier, $videos));
        self::assertSame([5, 3, 1, 0, 0], array_map(static fn ($video): int => $video->likeCount, $videos));
        self::assertSame(['en', 'ja', 'ja', 'ja', 'ja'], array_map(static fn ($video): string => $video->language, $videos));
    }

    public function testReturnsAnEmptyListWhenNoVideosExist(): void
    {
        self::assertSame([], app(ListPopularVideosInterface::class)->process());
    }

    public function testReturnsVideosEvenWhenNoLikesExist(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01']);
        $identifier = $this->video($wiki, 0);

        $videos = app(ListPopularVideosInterface::class)->process();
        self::assertCount(1, $videos);
        self::assertSame($identifier, $videos[0]->videoLinkIdentifier);
        self::assertSame(0, $videos[0]->likeCount);
    }

    public function testReturnsAtMostTenWithStableTieOrdering(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01']);
        $identifiers = [];
        for ($i = 0; $i < 22; ++$i) {
            $identifiers[] = $this->video($wiki, 1);
        }
        sort($identifiers);
        $videos = app(ListPopularVideosInterface::class)->process();
        self::assertSame(array_slice($identifiers, 0, 10), array_map(static fn ($video): string => $video->videoLinkIdentifier, $videos));
    }

    public function testDeduplicatesVideoIdentityAcrossWikisAndUrlFormatsBeforeTakingTen(): void
    {
        $wiki = StrTestHelper::generateUuid();
        $foreignWiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create($foreignWiki, 'group', ['language' => 'ko', 'published_at' => '2026-01-01']);
        $winner = $this->video($foreignWiki, 4, 'group', 'https://www.youtube.com/watch?v=abcdefghijk');
        // Higher-ranked duplicate links must not crowd out unique videos.
        for ($i = 0; $i < 25; ++$i) {
            $this->video($wiki, 2, 'talent', $i % 2 === 0
                ? 'https://youtu.be/abcdefghijk?t=' . $i
                : 'https://www.youtube.com/shorts/abcdefghijk?t=' . $i);
        }
        $unique = [];
        for ($i = 0; $i < 20; ++$i) {
            $unique[] = $this->video($wiki, 1);
        }
        sort($unique);

        $videos = app(ListPopularVideosInterface::class)->process();
        self::assertSame([$winner, ...array_slice($unique, 0, 9)], array_map(static fn ($video): string => $video->videoLinkIdentifier, $videos));
        self::assertCount(10, array_unique(array_map(static fn ($video): string => $video->provider . ':' . $video->externalVideoId, $videos)));
        self::assertSame('ko', $videos[0]->language);
    }

    public function testDeduplicatesTikTokIdsEvenWhenTheAccountNameDiffers(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01']);
        $winner = $this->video($wiki, 2, 'talent', 'https://www.tiktok.com/@newname/video/6718335390845095173');
        $this->video($wiki, 1, 'talent', 'https://www.tiktok.com/@oldname/video/6718335390845095173');

        $videos = app(ListPopularVideosInterface::class)->process();
        self::assertSame([$winner], array_map(static fn ($video): string => $video->videoLinkIdentifier, $videos));
    }

    private function video(string $wiki, int $likes, string $resourceType = 'talent', ?string $url = null): string
    {
        $id = StrTestHelper::generateUuid();
        CreateVideoLink::create($id, ['wiki_id' => $wiki, 'resource_type' => $resourceType, 'url' => $url ?? 'https://www.youtube.com/watch?v=' . substr(hash('sha256', $id), 0, 11), 'created_at' => '2026-01-01 00:00:00']);
        for ($i = 0; $i < $likes; ++$i) {
            $this->like($id);
        }

        return $id;
    }

    private function like(string $video, bool $official = false): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($principal, $identity);
        DB::table('wiki_video_link_likes')->insert([
            'id' => StrTestHelper::generateUuid(), 'video_link_id' => $video,
            'principal_id' => (string) $principal, 'is_official_owner' => $official,
            'created_at' => now(),
        ]);
    }
}
