<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;

class SetupIntentIdentifierTest extends TestCase
{
    public function testAcceptsIdentifier(): void
    {
        self::assertSame('seti_test123', (string) new SetupIntentIdentifier('seti_test123'));
    }

    public function testRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SetupIntentIdentifier('seti_test123_secret_bad');
    }
}
