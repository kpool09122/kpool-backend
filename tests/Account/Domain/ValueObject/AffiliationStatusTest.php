<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\AffiliationStatus;

class AffiliationStatusTest extends TestCase
{
    public function testPendingValue(): void
    {
        $this->assertSame('pending', AffiliationStatus::PENDING->value);
    }

    public function testActiveValue(): void
    {
        $this->assertSame('active', AffiliationStatus::ACTIVE->value);
    }

    public function testTerminatedValue(): void
    {
        $this->assertSame('terminated', AffiliationStatus::TERMINATED->value);
    }

    public function testIsPending(): void
    {
        $this->assertTrue(AffiliationStatus::PENDING->isPending());
        $this->assertFalse(AffiliationStatus::ACTIVE->isPending());
        $this->assertFalse(AffiliationStatus::TERMINATED->isPending());
    }

    public function testIsActive(): void
    {
        $this->assertFalse(AffiliationStatus::PENDING->isActive());
        $this->assertTrue(AffiliationStatus::ACTIVE->isActive());
        $this->assertFalse(AffiliationStatus::TERMINATED->isActive());
    }

    public function testIsTerminated(): void
    {
        $this->assertFalse(AffiliationStatus::PENDING->isTerminated());
        $this->assertFalse(AffiliationStatus::ACTIVE->isTerminated());
        $this->assertTrue(AffiliationStatus::TERMINATED->isTerminated());
    }
}
