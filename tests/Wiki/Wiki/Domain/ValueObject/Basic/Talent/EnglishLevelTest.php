<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\EnglishLevel;

class EnglishLevelTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'NATIVE' => 'native',
            'FLUENT' => 'fluent',
            'CONVERSATIONAL' => 'conversational',
            'BASIC' => 'basic',
            'NONE' => 'none',
        ], array_column(EnglishLevel::cases(), 'value', 'name'));
    }
}
