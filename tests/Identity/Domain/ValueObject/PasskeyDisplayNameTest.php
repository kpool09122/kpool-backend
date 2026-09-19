<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;

class PasskeyDisplayNameTest extends TestCase
{
    #[DataProvider('validDisplayNameProvider')]
    public function testItAcceptsADisplayNameWithinTheAllowedLength(string $value): void
    {
        $this->assertSame($value, (string) new PasskeyDisplayName($value));
    }

    /** @return array<string, array{string}> */
    public static function validDisplayNameProvider(): array
    {
        return [
            'minimum' => ['a'],
            'maximum ASCII' => [str_repeat('a', PasskeyDisplayName::MAX_LENGTH)],
            'maximum multibyte' => [str_repeat('あ', PasskeyDisplayName::MAX_LENGTH)],
        ];
    }

    #[DataProvider('invalidDisplayNameProvider')]
    public function testItRejectsADisplayNameOutsideTheAllowedLength(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PasskeyDisplayName($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidDisplayNameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => [' '],
            'longer than maximum ASCII' => [str_repeat('a', PasskeyDisplayName::MAX_LENGTH + 1)],
            'longer than maximum multibyte' => [str_repeat('あ', PasskeyDisplayName::MAX_LENGTH + 1)],
        ];
    }
}
