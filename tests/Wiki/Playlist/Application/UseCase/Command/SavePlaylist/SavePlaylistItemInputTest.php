<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistItemInput;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\StrTestHelper;

class SavePlaylistItemInputTest extends TestCase
{
    public function testPreservesExistingVideoPairOrNewSource(): void
    {
        $id = new ExternalVideoId('abcdefghijk');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $existing = new SavePlaylistItemInput(provider: VideoProvider::YOUTUBE, externalVideoId: $id);
        $added = new SavePlaylistItemInput(videoLinkIdentifier: $video);
        self::assertSame(VideoProvider::YOUTUBE, $existing->provider);
        self::assertSame($id, $existing->externalVideoId);
        self::assertNull($existing->videoLinkIdentifier);
        self::assertSame($video, $added->videoLinkIdentifier);
        self::assertNull($added->provider);
        self::assertNull($added->externalVideoId);
    }

    public function testRejectsNeitherIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SavePlaylistItemInput();
    }

    public function testRejectsBothIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SavePlaylistItemInput(VideoProvider::YOUTUBE, new ExternalVideoId('abcdefghijk'), new VideoLinkIdentifier(StrTestHelper::generateUuid()));
    }

    /** @return array<string, array{?VideoProvider, ?ExternalVideoId, bool}> */
    public static function incompleteExistingSelections(): array
    {
        return [
            'only provider' => [VideoProvider::YOUTUBE, null, false],
            'only external ID' => [null, new ExternalVideoId('abcdefghijk'), false],
            'source and provider' => [VideoProvider::YOUTUBE, null, true],
            'source and external ID' => [null, new ExternalVideoId('abcdefghijk'), true],
        ];
    }

    #[DataProvider('incompleteExistingSelections')]
    public function testRejectsIncompleteExistingSelection(?VideoProvider $provider, ?ExternalVideoId $externalVideoId, bool $withSource): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SavePlaylistItemInput($provider, $externalVideoId, $withSource ? new VideoLinkIdentifier(StrTestHelper::generateUuid()) : null);
    }
}
