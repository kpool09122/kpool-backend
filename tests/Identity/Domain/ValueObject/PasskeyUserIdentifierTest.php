<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Tests\TestCase;

class PasskeyUserIdentifierTest extends TestCase
{
    public function testItAcceptsAUuid(): void
    {
        $value = '123e4567-e89b-72d3-a456-426614174000';

        $this->assertSame($value, (string) new PasskeyUserIdentifier($value));
    }

    #[DataProvider('invalidIdentifiers')]
    public function testItRejectsAnInvalidIdentifier(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PasskeyUserIdentifier($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidIdentifiers(): array
    {
        return [
            'empty' => [''],
            'not uuid' => ['user@example.com'],
        ];
    }
}
