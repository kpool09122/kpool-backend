<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\AccountCategory;

class AccountCategoryTest extends TestCase
{
    public function testAgencyValue(): void
    {
        $this->assertSame('agency', AccountCategory::AGENCY->value);
    }

    public function testTalentValue(): void
    {
        $this->assertSame('talent', AccountCategory::TALENT->value);
    }

    public function testGeneralValue(): void
    {
        $this->assertSame('general', AccountCategory::GENERAL->value);
    }

    public function testIsAgency(): void
    {
        $this->assertTrue(AccountCategory::AGENCY->isAgency());
        $this->assertFalse(AccountCategory::TALENT->isAgency());
        $this->assertFalse(AccountCategory::GENERAL->isAgency());
    }

    public function testIsTalent(): void
    {
        $this->assertFalse(AccountCategory::AGENCY->isTalent());
        $this->assertTrue(AccountCategory::TALENT->isTalent());
        $this->assertFalse(AccountCategory::GENERAL->isTalent());
    }

    public function testIsGeneral(): void
    {
        $this->assertFalse(AccountCategory::AGENCY->isGeneral());
        $this->assertFalse(AccountCategory::TALENT->isGeneral());
        $this->assertTrue(AccountCategory::GENERAL->isGeneral());
    }
}
