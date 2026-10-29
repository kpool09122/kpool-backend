<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Infrastructure\Factory\VideoLinkWatchFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkWatchFactoryTest extends TestCase
{
    public function testCreatesWatchWithGeneratedIdentifierAndServerCompletionTime(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects(self::once())->method('generate')->willReturn($uuid);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $before = new DateTimeImmutable();
        $watch = (new VideoLinkWatchFactory($uuidGenerator))->create($video, $owner, $principal);
        self::assertSame($uuid, (string) $watch->videoLinkWatchIdentifier());
        self::assertSame($video, $watch->videoLinkIdentifier());
        self::assertSame($owner, $watch->ownerAccountIdentifier());
        self::assertSame($principal, $watch->principalIdentifier());
        self::assertGreaterThanOrEqual($before, $watch->watchedAt());
        self::assertLessThanOrEqual(new DateTimeImmutable(), $watch->watchedAt());
    }
}
