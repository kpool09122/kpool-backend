<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\AffiliationTerms;
use Source\Monetization\Shared\ValueObject\Percentage;

class AffiliationTermsTest extends TestCase
{
    public function test__construct(): void
    {
        $percentage = new Percentage(30);
        $notes = 'Contract notes';

        $terms = new AffiliationTerms($percentage, $notes);

        $this->assertSame($percentage, $terms->revenueSharePercentage());
        $this->assertSame($notes, $terms->contractNotes());
    }

    public function testNullableValues(): void
    {
        $terms = new AffiliationTerms(null, null);

        $this->assertNull($terms->revenueSharePercentage());
        $this->assertNull($terms->contractNotes());
    }

    public function testPartiallyNullValues(): void
    {
        $percentage = new Percentage(50);
        $terms = new AffiliationTerms($percentage, null);

        $this->assertSame($percentage, $terms->revenueSharePercentage());
        $this->assertNull($terms->contractNotes());
    }
}
