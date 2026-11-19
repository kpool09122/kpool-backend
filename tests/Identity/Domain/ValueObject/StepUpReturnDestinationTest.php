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
            'EXTERNAL_CONNECTIONS' => 'external-connections',
            'AUTHORIZATION' => 'authorization',
        ], array_column(StepUpReturnDestination::cases(), 'value', 'name'));
    }
}
