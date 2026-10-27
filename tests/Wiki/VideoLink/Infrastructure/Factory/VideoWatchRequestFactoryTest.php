<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Infrastructure\Factory\VideoWatchRequestFactory;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class VideoWatchRequestFactoryTest extends TestCase
{
    public function testCreatesRequestWithGeneratedIdAndPreservesAllValues(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects(self::once())->method('generate')->willReturn($uuid);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        $sentAt = new DateTimeImmutable();
        $request = (new VideoWatchRequestFactory($uuidGenerator))->create($video, $wiki, $principal, $sentAt);
        self::assertSame($uuid, (string) $request->videoWatchRequestIdentifier());
        self::assertSame($video, $request->videoLinkIdentifier());
        self::assertSame($principal, $request->principalIdentifier());
        self::assertSame($wiki, $request->wikiIdentifier());
        self::assertSame($sentAt, $request->sentAt());
    }
}
