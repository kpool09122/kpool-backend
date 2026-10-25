<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInput;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class CreatePlaylistInputTest extends TestCase
{
    public function testPreservesValueObjectInstances(): void
    {
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $name = new PlaylistName('曲');
        $input = new CreatePlaylistInput($owner, $name);
        self::assertSame($owner, $input->principalIdentifier());
        self::assertSame($name, $input->name());
    }
}
