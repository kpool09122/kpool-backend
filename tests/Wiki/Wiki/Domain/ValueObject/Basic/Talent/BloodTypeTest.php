<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\BloodType;

class BloodTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'A' => 'A',
            'B' => 'B',
            'O' => 'O',
            'AB' => 'AB',
        ], array_column(BloodType::cases(), 'value', 'name'));
    }
}
