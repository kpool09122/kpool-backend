<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupStatus;

class GroupStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACTIVE' => 'active',
            'DISBANDED' => 'disbanded',
            'HIATUS' => 'hiatus',
        ], array_column(GroupStatus::cases(), 'value', 'name'));
    }
}
