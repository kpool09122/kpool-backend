<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongSource;

class AutomaticDraftSongSourceTest extends TestCase
{
    public function test__construct(): void
    {
        $source = 'news::song-id';
        $automaticDraftSongSource = new AutomaticDraftSongSource($source);

        $this->assertSame($source, (string) $automaticDraftSongSource);
    }

    public function testWithEmptyValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftSongSource('');
    }

    public function testWithTooLongValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AutomaticDraftSongSource(str_repeat('a', AutomaticDraftSongSource::MAX_LENGTH + 1));
    }
}
