<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ListMyPlaylistsInputTest extends TestCase
{
    public function testPreservesPrincipalInstance(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        self::assertSame($principal, (new ListMyPlaylistsInput($principal))->principalIdentifier());
    }
}
