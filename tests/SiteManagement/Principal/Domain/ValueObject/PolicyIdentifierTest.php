<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;

class PolicyIdentifierTest extends TestCase
{
    public function testIdentifierRoundTrips(): void
    {
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) new PolicyIdentifier('69200000-0000-7000-8000-000000000099'));
    }
}
