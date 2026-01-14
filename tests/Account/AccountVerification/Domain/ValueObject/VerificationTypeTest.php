<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;

class VerificationTypeTest extends TestCase
{
    public function testIsTalent(): void
    {
        $this->assertTrue(VerificationType::TALENT->isTalent());
        $this->assertFalse(VerificationType::AGENCY->isTalent());
    }

    public function testIsAgency(): void
    {
        $this->assertTrue(VerificationType::AGENCY->isAgency());
        $this->assertFalse(VerificationType::TALENT->isAgency());
    }

    public function testToAccountCategory(): void
    {
        $this->assertSame(AccountCategory::TALENT, VerificationType::TALENT->toAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, VerificationType::AGENCY->toAccountCategory());
    }
}
