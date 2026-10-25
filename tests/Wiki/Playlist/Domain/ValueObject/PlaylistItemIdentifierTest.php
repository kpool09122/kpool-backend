<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemIdentifier;
use Tests\Helper\StrTestHelper;

class PlaylistItemIdentifierTest extends TestCase
{
    public function testAcceptsUuidAndRejectsInvalidValue(): void
    {
        $id = StrTestHelper::generateUuid();
        self::assertSame($id, (string) new PlaylistItemIdentifier($id));
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItemIdentifier('invalid');
    }
}
