<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\ValueObject\PasskeyUserHandle;
use Tests\TestCase;

class PasskeyUserHandleTest extends TestCase
{
    public function testItAcceptsAUuid(): void
    {
        $value = '123e4567-e89b-72d3-a456-426614174000';

        $this->assertSame($value, (string) new PasskeyUserHandle($value));
    }

    #[DataProvider('invalidHandles')]
    public function testItRejectsAnInvalidHandle(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PasskeyUserHandle($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidHandles(): array
    {
        return [
            'empty' => [''],
            'not uuid' => ['user@example.com'],
        ];
    }
}
