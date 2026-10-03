<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\Generation;

class GenerationTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'FIRST' => '1st',
            'SECOND' => '2nd',
            'THIRD' => '3rd',
            'FOURTH' => '4th',
            'FIFTH' => '5th',
        ], array_column(Generation::cases(), 'value', 'name'));
    }
}
