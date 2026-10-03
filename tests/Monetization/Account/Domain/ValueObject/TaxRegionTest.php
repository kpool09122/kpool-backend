<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\TaxRegion;

class TaxRegionTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'JP' => 'JP',
            'KR' => 'KR',
            'US' => 'US',
        ], array_column(TaxRegion::cases(), 'value', 'name'));
    }
}
