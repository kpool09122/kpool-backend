<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AddressLine;

class AddressLineTest extends TestCase
{
    public function testTrimsAndPreservesMaximumLength(): void
    {
        $value = str_repeat('あ', AddressLine::MAX_LENGTH);
        $this->assertSame($value, (string) new AddressLine('  ' . $value . '  '));
    }

    public function testRejectsBlankValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AddressLine('   ');
    }

    public function testRejectsTooLongValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AddressLine(str_repeat('あ', AddressLine::MAX_LENGTH + 1));
    }
}
