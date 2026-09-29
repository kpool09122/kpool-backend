<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class ChallengeSessionKeyTest extends TestCase
{
    public function testItAcceptsAUuidVersion7(): void
    {
        $value = '123e4567-e89b-72d3-a456-426614174010';

        $this->assertSame($value, (string) new ChallengeSessionKey($value));
    }

    #[DataProvider('invalidKeyProvider')]
    public function testItRejectsAnInvalidKey(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChallengeSessionKey($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidKeyProvider(): array
    {
        return [
            'empty' => [''],
            'malformed' => ['not-a-uuid'],
            'uuid version 4' => ['123e4567-e89b-42d3-a456-426614174010'],
        ];
    }
}
