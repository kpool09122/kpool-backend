<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\TaxCategory;

class TaxCategoryTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'TAXABLE' => 'taxable',
            'EXEMPT' => 'exempt',
            'REVERSE_CHARGE' => 'reverse_charge',
        ], array_column(TaxCategory::cases(), 'value', 'name'));
    }
}
