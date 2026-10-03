<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\StepUpReturnDestination;

class StepUpReturnDestinationTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'PASSKEYS' => 'passkeys',
            'WITHDRAWAL' => 'withdrawal',
        ], array_column(StepUpReturnDestination::cases(), 'value', 'name'));
    }
}
