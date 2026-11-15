<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;

class AccountPrincipalIdentifierTest extends TestCase
{
    public function testPreservesAValidIdentifier(): void
    {
        $value = '0199f700-0000-7000-8000-000000000001';

        self::assertSame($value, (string) new AccountPrincipalIdentifier($value));
    }

    public function testRejectsAnInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AccountPrincipalIdentifier('invalid');
    }
}
