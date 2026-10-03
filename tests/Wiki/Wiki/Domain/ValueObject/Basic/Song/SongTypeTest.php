<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongType;

class SongTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'TITLE_TRACK' => 'title_track',
            'B_SIDE' => 'b_side',
            'OST' => 'ost',
            'SOLO' => 'solo',
            'COLLABORATION' => 'collaboration',
            'PRE_RELEASE' => 'pre_release',
        ], array_column(SongType::cases(), 'value', 'name'));
    }
}
