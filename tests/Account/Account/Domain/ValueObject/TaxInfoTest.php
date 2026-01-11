<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Account\Domain\ValueObject\TaxRegion;

class TaxInfoTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $region = TaxRegion::KR;
        $category = TaxCategory::TAXABLE;
        $taxCode = 'A23456789012';
        $taxInfo = new TaxInfo(
            $region,
            $category,
            $taxCode
        );
        $this->assertSame($region, $taxInfo->region());
        $this->assertSame($category, $taxInfo->category());
        $this->assertSame($taxCode, $taxInfo->taxCode());

        $taxInfo = new TaxInfo(
            $region,
            $category,
        );
        $this->assertNull($taxInfo->taxCode());
    }
}
