<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;

class PasskeyCredentialIdentifierTest extends TestCase
{
    public function testItAcceptsAUuidVersion7(): void
    {
        $value = '123e4567-e89b-72d3-a456-426614174001';

        $this->assertSame($value, (string) new PasskeyCredentialIdentifier($value));
    }

    #[DataProvider('invalidIdentifierProvider')]
    public function testItRejectsAnInvalidIdentifier(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PasskeyCredentialIdentifier($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidIdentifierProvider(): array
    {
        return [
            'empty' => [''],
            'malformed' => ['not-a-uuid'],
            'uuid version 4' => ['123e4567-e89b-42d3-a456-426614174001'],
        ];
    }
}
