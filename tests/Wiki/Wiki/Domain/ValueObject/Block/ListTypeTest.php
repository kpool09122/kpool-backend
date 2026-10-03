<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ListType;

class ListTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'BULLET' => 'bullet',
            'NUMBERED' => 'numbered',
        ], array_column(ListType::cases(), 'value', 'name'));
    }
}
