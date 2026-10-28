<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Factory\VideoWatchRequestFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoWatchRequestRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoWatchRequestRepositoryTest extends TestCase
{
    public function testPersistsHistoryAndScopesLatestAndRollingWindowWithoutDeletingExpiredRows(): void
    {
        $repository = $this->app()->make(VideoWatchRequestRepositoryInterface::class);
        $factory = $this->app()->make(VideoWatchRequestFactoryInterface::class);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $other = new PrincipalIdentifier(StrTestHelper::generateUuid());
        self::assertNull($repository->findLatestByVideoLink($video));
        self::assertNull($repository->findLatestByVideoLinkAndPrincipal($video, $principal));
        foreach (['2026-10-20 23:59:59 UTC','2026-10-21 00:00:00 UTC','2026-10-21 00:00:01 UTC'] as $time) {
            $repository->save($factory->create($video, $wiki, $principal, new DateTimeImmutable($time)));
        }
        $latest = $factory->create($video, $wiki, $other, new DateTimeImmutable('2026-10-22 00:00:00 UTC'));
        $repository->save($latest);
        $rows = $repository->findByWikiAndPrincipal($wiki, $principal, new DateTimeImmutable('2026-10-21 00:00:00 UTC'));
        self::assertCount(1, $rows);
        self::assertSame('2026-10-21 00:00:01', $rows[0]->sentAt()->format('Y-m-d H:i:s'));
        self::assertSame((string) $wiki, (string) $rows[0]->wikiIdentifier());
        self::assertSame((string) $principal, (string) $rows[0]->principalIdentifier());
        self::assertSame((string) $video, (string) $rows[0]->videoLinkIdentifier());
        self::assertSame((string) $latest->videoWatchRequestIdentifier(), (string) $repository->findLatestByVideoLink($video)?->videoWatchRequestIdentifier());
        self::assertSame('2026-10-21 00:00:01', $repository->findLatestByVideoLinkAndPrincipal($video, $principal)?->sentAt()->format('Y-m-d H:i:s'));
        self::assertSame([], $repository->findByWikiAndPrincipal(new WikiIdentifier(StrTestHelper::generateUuid()), $principal, new DateTimeImmutable('2000-01-01')));
        self::assertSame([], $repository->findByWikiAndPrincipal($wiki, new PrincipalIdentifier(StrTestHelper::generateUuid()), new DateTimeImmutable('2000-01-01')));
        self::assertCount(3, $repository->findByWikiAndPrincipal($wiki, $principal, new DateTimeImmutable('2000-01-01')));
    }
}
