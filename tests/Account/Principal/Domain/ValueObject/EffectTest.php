<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\Effect;

class EffectTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ALLOW' => 'allow',
            'DENY' => 'deny',
        ], array_column(Effect::cases(), 'value', 'name'));
    }
}
