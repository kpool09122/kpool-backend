<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongGenre;

class SongGenreTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'POP' => 'pop',
            'DANCE' => 'dance',
            'BALLAD' => 'ballad',
            'RNB' => 'rnb',
            'HIPHOP' => 'hiphop',
            'EDM' => 'edm',
            'ROCK' => 'rock',
            'JAZZ' => 'jazz',
            'ACOUSTIC' => 'acoustic',
        ], array_column(SongGenre::cases(), 'value', 'name'));
    }
}
