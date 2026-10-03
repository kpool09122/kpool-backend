<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

class PasskeyRecoveryKeyTest extends TestCase
{
    public function testPreservesUuid(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000001';
        $this->assertSame($uuid, (string) new PasskeyRecoveryKey($uuid));
    }

    public function testRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PasskeyRecoveryKey('invalid');
    }
}
