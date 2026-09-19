<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\CredentialSource;

class CredentialSourceTest extends TestCase
{
    public function testItAcceptsAJsonObject(): void
    {
        $value = '{"credential":{"counter":1}}';

        $this->assertSame($value, (string) new CredentialSource($value));
    }

    #[DataProvider('invalidJsonProvider')]
    public function testItRejectsAValueThatIsNotAJsonObject(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CredentialSource($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidJsonProvider(): array
    {
        return [
            'malformed JSON' => ['{"credential":'],
            'array' => ['[]'],
            'string' => ['"credential"'],
            'number' => ['1'],
            'boolean' => ['true'],
            'null' => ['null'],
        ];
    }
}
