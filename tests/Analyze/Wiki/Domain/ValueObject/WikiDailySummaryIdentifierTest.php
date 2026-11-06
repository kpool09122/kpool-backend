<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;

class WikiDailySummaryIdentifierTest extends TestCase
{
    public function testIdentifierRoundTrips(): void
    {
        $identifier = '69200000-0000-7000-8000-000000000099';
        self::assertSame($identifier, (string) new WikiDailySummaryIdentifier($identifier));
    }

    public function testInvalidIdentifierIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WikiDailySummaryIdentifier('invalid');
    }
}
