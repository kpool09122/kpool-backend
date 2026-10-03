<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\EmbedProvider;

class EmbedProviderTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'YOUTUBE' => 'youtube',
            'SPOTIFY' => 'spotify',
            'X' => 'x',
            'TIKTOK' => 'tiktok',
        ], array_column(EmbedProvider::cases(), 'value', 'name'));
    }
}
