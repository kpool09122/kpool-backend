<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInput;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class DeletePlaylistInputTest extends TestCase
{
    public function testPreservesTheSuppliedIdentifiers(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $playlist = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $input = new DeletePlaylistInput($principal, $playlist);

        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame($playlist, $input->playlistIdentifier());
    }
}
