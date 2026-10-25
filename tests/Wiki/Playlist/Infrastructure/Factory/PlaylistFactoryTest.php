<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Infrastructure\Factory;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Infrastructure\Factory\PlaylistFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PlaylistFactoryTest extends MockeryTestCase
{
    public function testCreatesAnEmptyOwnedPlaylistWithGeneratedIdentifierAndSameTimestamps(): void
    {
        $id = StrTestHelper::generateUuid();
        /** @var UuidGeneratorInterface&Mockery\MockInterface $uuidGenerator */
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->once()->andReturn($id);
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $name = new PlaylistName('My playlist');
        $before = new DateTimeImmutable();
        $playlist = (new PlaylistFactory($uuidGenerator))->create($owner, $name);
        self::assertSame($id, (string) $playlist->playlistIdentifier());
        self::assertSame($owner, $playlist->principalIdentifier());
        self::assertSame($name, $playlist->name());
        self::assertSame([], $playlist->items());
        self::assertSame($playlist->createdAt(), $playlist->updatedAt());
        self::assertGreaterThanOrEqual($before, $playlist->createdAt());
        self::assertLessThanOrEqual(new DateTimeImmutable(), $playlist->createdAt());
    }
}
