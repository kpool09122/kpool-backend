<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupType;

class GroupTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'BOY_GROUP' => 'boy_group',
            'GIRL_GROUP' => 'girl_group',
            'CO_ED' => 'co_ed',
        ], array_column(GroupType::cases(), 'value', 'name'));
    }
}
