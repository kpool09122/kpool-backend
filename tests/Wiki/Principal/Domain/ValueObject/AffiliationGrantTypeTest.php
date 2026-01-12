<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;

class AffiliationGrantTypeTest extends TestCase
{
    /**
     * 正常系: TALENT_SIDE が正しく生成されること
     */
    public function testTalentSide(): void
    {
        $type = AffiliationGrantType::TALENT_SIDE;
        $this->assertSame('talent_side', $type->value);
    }

    /**
     * 正常系: AGENCY_SIDE が正しく生成されること
     */
    public function testAgencySide(): void
    {
        $type = AffiliationGrantType::AGENCY_SIDE;
        $this->assertSame('agency_side', $type->value);
    }

    /**
     * 正常系: 文字列からインスタンスが生成できること
     */
    public function testFromString(): void
    {
        $talentSide = AffiliationGrantType::from('talent_side');
        $agencySide = AffiliationGrantType::from('agency_side');

        $this->assertSame(AffiliationGrantType::TALENT_SIDE, $talentSide);
        $this->assertSame(AffiliationGrantType::AGENCY_SIDE, $agencySide);
    }
}
