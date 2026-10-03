<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Currency;

class CurrencyTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'JPY' => 'JPY',
            'USD' => 'USD',
            'KRW' => 'KRW',
        ], array_column(Currency::cases(), 'value', 'name'));
    }
}
