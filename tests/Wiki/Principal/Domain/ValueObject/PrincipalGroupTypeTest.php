<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupType;

class PrincipalGroupTypeTest extends TestCase
{
    /**
     * 正常系: DEFAULT が正しく生成されること
     */
    public function testDefault(): void
    {
        $type = PrincipalGroupType::DEFAULT;
        $this->assertSame('default', $type->value);
    }

    /**
     * 正常系: CUSTOM が正しく生成されること
     */
    public function testCustom(): void
    {
        $type = PrincipalGroupType::CUSTOM;
        $this->assertSame('custom', $type->value);
    }

    /**
     * 正常系: AFFILIATION_TALENT が正しく生成されること
     */
    public function testAffiliationTalent(): void
    {
        $type = PrincipalGroupType::AFFILIATION_TALENT;
        $this->assertSame('affiliation_talent', $type->value);
    }

    /**
     * 正常系: AFFILIATION_AGENCY が正しく生成されること
     */
    public function testAffiliationAgency(): void
    {
        $type = PrincipalGroupType::AFFILIATION_AGENCY;
        $this->assertSame('affiliation_agency', $type->value);
    }

    /**
     * 正常系: isAffiliationType が正しく判定すること
     */
    public function testIsAffiliationType(): void
    {
        $this->assertFalse(PrincipalGroupType::DEFAULT->isAffiliationType());
        $this->assertFalse(PrincipalGroupType::CUSTOM->isAffiliationType());
        $this->assertTrue(PrincipalGroupType::AFFILIATION_TALENT->isAffiliationType());
        $this->assertTrue(PrincipalGroupType::AFFILIATION_AGENCY->isAffiliationType());
    }

    /**
     * 正常系: 文字列からインスタンスが生成できること
     */
    public function testFromString(): void
    {
        $default = PrincipalGroupType::from('default');
        $custom = PrincipalGroupType::from('custom');
        $affiliationTalent = PrincipalGroupType::from('affiliation_talent');
        $affiliationAgency = PrincipalGroupType::from('affiliation_agency');

        $this->assertSame(PrincipalGroupType::DEFAULT, $default);
        $this->assertSame(PrincipalGroupType::CUSTOM, $custom);
        $this->assertSame(PrincipalGroupType::AFFILIATION_TALENT, $affiliationTalent);
        $this->assertSame(PrincipalGroupType::AFFILIATION_AGENCY, $affiliationAgency);
    }
}
