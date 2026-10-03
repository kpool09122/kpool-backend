<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Agency;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyStatus;

class AgencyStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACTIVE' => 'active',
            'CLOSED' => 'closed',
            'MERGED' => 'merged',
            'REBRANDED' => 'rebranded',
        ], array_column(AgencyStatus::cases(), 'value', 'name'));
    }
}
