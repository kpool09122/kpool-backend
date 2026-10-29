<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkWatchRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkWatchIdentifier;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoLinkWatchRepositoryTest extends TestCase
{
    public function testRecordsTheFirstCompletionPerVideoAndAccountAndCascadesOnVideoDeletion(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki]);
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $other = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app()->make(VideoLinkWatchRepositoryInterface::class);
        self::assertNull($repository->findByVideoLinkAndOwnerAccount($video, $account));
        $watch = new VideoLinkWatch(new VideoLinkWatchIdentifier(StrTestHelper::generateUuid()), $video, $account, $principal, new DateTimeImmutable('2026-01-01 00:00:00'));
        $repository->save($watch);
        $repository->save(new VideoLinkWatch(new VideoLinkWatchIdentifier(StrTestHelper::generateUuid()), $video, $account, new PrincipalIdentifier(StrTestHelper::generateUuid()), new DateTimeImmutable('2026-01-02 00:00:00')));
        $restored = $repository->findByVideoLinkAndOwnerAccount($video, $account);
        self::assertNotNull($restored);
        self::assertEquals($watch, $restored);
        self::assertNull($repository->findByVideoLinkAndOwnerAccount($video, $other));
        self::assertSame(1, DB::table('wiki_video_link_watches')->count());
        $this->assertDatabaseHas('wiki_video_link_watches', ['principal_id' => (string) $principal, 'watched_at' => '2026-01-01 00:00:00']);
        $repository->save(new VideoLinkWatch(new VideoLinkWatchIdentifier(StrTestHelper::generateUuid()), $video, $other, $principal, new DateTimeImmutable()));
        self::assertSame(2, DB::table('wiki_video_link_watches')->count());
        DB::table('wiki_video_links')->where('id', (string) $video)->delete();
        self::assertNull($repository->findByVideoLinkAndOwnerAccount($video, $account));
        self::assertSame(0, DB::table('wiki_video_link_watches')->count());
    }
}
