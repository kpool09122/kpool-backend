<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;

class TaxDocumentTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'JP_QUALIFIED_INVOICE' => 'jp_qualified_invoice',
            'KR_ELECTRONIC_TAX_INVOICE' => 'kr_electronic_tax_invoice',
            'REVERSE_CHARGE_NOTICE' => 'reverse_charge_notice',
            'CARD_RECEIPT' => 'card_receipt',
            'CASH_RECEIPT' => 'cash_receipt',
            'SIMPLE_RECEIPT' => 'simple_receipt',
        ], array_column(TaxDocumentType::cases(), 'value', 'name'));
    }
}
