<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

class PrincipalGroupIdentifierTest extends TestCase
{
    public function testRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrincipalGroupIdentifier('invalid-identifier');
    }

    public function testIdentifierRoundTrips(): void
    {
        self::assertSame('69200000-0000-7000-8000-000000000099', (string) new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099'));
    }
}
