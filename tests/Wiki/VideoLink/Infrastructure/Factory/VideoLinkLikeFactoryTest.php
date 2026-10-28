<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Infrastructure\Factory\VideoLinkLikeFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkLikeFactoryTest extends TestCase
{
    public function testCreatesIndependentLikeWithVideoPrincipalAndCreationTime(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects(self::once())->method('generate')->willReturn($uuid);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $before = new DateTimeImmutable();
        $like = (new VideoLinkLikeFactory($uuidGenerator))->create($video, $principal, false);
        self::assertSame($uuid, (string) $like->videoLinkLikeIdentifier());
        self::assertSame($video, $like->videoLinkIdentifier());
        self::assertSame($principal, $like->principalIdentifier());
        self::assertFalse($like->isOfficialOwner());
        self::assertGreaterThanOrEqual($before, $like->createdAt());
        self::assertLessThanOrEqual(new DateTimeImmutable(), $like->createdAt());
    }
}
