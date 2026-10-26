<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInput;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class GetMyPlaylistInputTest extends TestCase
{
    public function testPreservesValueObjectInstances(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $playlist = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $input = new GetMyPlaylistInput($principal, $playlist);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame($playlist, $input->playlistIdentifier());
    }
}
