<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\VideoLink\Infrastructure\Service\VideoWatchRequestDigestQueryService;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoWatchRequestDigestQueryServiceTest extends TestCase
{
    public function testReturnsOnlyPublishedOfficialWikiWithNewRequests(): void
    {
        $owner = StrTestHelper::generateUuid();
        CreateAccount::create($owner);
        foreach (['eligible', 'unpublished', 'unofficial', 'no-new-requests'] as $scenario) {
            $wiki = StrTestHelper::generateUuid();
            $video = StrTestHelper::generateUuid();
            CreateWiki::create($wiki, 'talent', [
                'slug' => 'tl-' . $scenario,
                'owner_account_id' => $scenario === 'unofficial' ? null : $owner,
                'published_at' => $scenario === 'unpublished' ? null : '2026-01-01',
            ]);
            CreateVideoLink::create($video, ['wiki_id' => $wiki]);
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $video, 'wiki_id' => $wiki, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => $scenario === 'no-new-requests' ? '2026-10-26 00:00:00' : '2026-10-27 00:00:00']);
        }
        $digests = iterator_to_array((new VideoWatchRequestDigestQueryService())->findByPeriod(
            new DateTimeImmutable('2026-10-27T00:00:00Z'),
            new DateTimeImmutable('2026-10-21T00:00:00Z'),
            new DateTimeImmutable('2026-10-28T00:00:00Z'),
        ));
        self::assertCount(1, $digests);
        self::assertSame($owner, (string) $digests[0]->ownerAccountIdentifier);
        self::assertSame('tl-eligible', $digests[0]->wikiSlug);
        self::assertSame(Language::KOREAN, $digests[0]->wikiLanguage);
        self::assertSame(1, $digests[0]->recentRequestCount);
        self::assertSame(1, $digests[0]->newRequestCount);
    }
}
