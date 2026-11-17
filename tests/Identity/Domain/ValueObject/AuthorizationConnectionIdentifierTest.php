<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Tests\TestCase;

class AuthorizationConnectionIdentifierTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function validIdentifiers(): array
    {
        return [
            'uuid v4' => ['123e4567-e89b-42d3-a456-426614174000'],
            'uuid v7' => ['123e4567-e89b-72d3-a456-426614174000'],
        ];
    }

    #[DataProvider('validIdentifiers')]
    public function testItAcceptsAUuid(string $value): void
    {
        $this->assertSame($value, (string) new AuthorizationConnectionIdentifier($value));
    }

    #[DataProvider('invalidIdentifiers')]
    public function testItRejectsAnInvalidIdentifier(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthorizationConnectionIdentifier($value);
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
