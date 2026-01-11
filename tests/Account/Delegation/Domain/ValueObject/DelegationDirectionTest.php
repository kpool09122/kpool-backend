<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;

class DelegationDirectionTest extends TestCase
{
    /**
     * 正常系: FROM_AGENCY の値が正しいこと
     *
     * @return void
     */
    public function testFromAgencyValue(): void
    {
        $this->assertSame('from_agency', DelegationDirection::FROM_AGENCY->value);
    }

    /**
     * 正常系: FROM_TALENT の値が正しいこと
     *
     * @return void
     */
    public function testFromTalentValue(): void
    {
        $this->assertSame('from_talent', DelegationDirection::FROM_TALENT->value);
    }

    /**
     * 正常系: isFromAgency() が正しく動作すること
     *
     * @return void
     */
    public function testIsFromAgency(): void
    {
        $this->assertTrue(DelegationDirection::FROM_AGENCY->isFromAgency());
        $this->assertFalse(DelegationDirection::FROM_TALENT->isFromAgency());
    }

    /**
     * 正常系: isFromTalent() が正しく動作すること
     *
     * @return void
     */
    public function testIsFromTalent(): void
    {
        $this->assertTrue(DelegationDirection::FROM_TALENT->isFromTalent());
        $this->assertFalse(DelegationDirection::FROM_AGENCY->isFromTalent());
    }
}
