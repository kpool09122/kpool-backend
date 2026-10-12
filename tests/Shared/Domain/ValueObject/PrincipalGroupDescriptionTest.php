<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;

class PrincipalGroupDescriptionTest extends TestCase
{
    #[DataProvider('acceptedDescriptions')]
    public function testPreservesDescriptionsUpTo256Characters(string $value): void
    {
        self::assertSame($value, (string) new PrincipalGroupDescription($value));
    }

    /** @return array<string, array{string}> */
    public static function acceptedDescriptions(): array
    {
        return ['empty' => [''], 'ascii boundary' => [str_repeat('a', 256)], 'unicode boundary' => [str_repeat('説', 256)], 'emoji boundary' => [str_repeat('😀', 256)]];
    }

    #[DataProvider('overlongDescriptions')]
    public function testRejectsDescriptionsLongerThan256Characters(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrincipalGroupDescription($value);
    }

    /** @return array<string, array{string}> */
    public static function overlongDescriptions(): array
    {
        return ['ascii' => [str_repeat('a', 257)], 'unicode' => [str_repeat('説', 257)], 'whitespace' => [str_repeat(' ', 257)]];
    }
}
