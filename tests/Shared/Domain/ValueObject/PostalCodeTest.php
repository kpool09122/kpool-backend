<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\PostalCode;

class PostalCodeTest extends TestCase
{
    public function testTrimsAndPreservesMaximumLength(): void
    {
        $value = str_repeat('あ', PostalCode::MAX_LENGTH);
        $this->assertSame($value, (string) new PostalCode('  ' . $value . '  '));
    }

    public function testRejectsBlankValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostalCode('   ');
    }

    public function testRejectsTooLongValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostalCode(str_repeat('あ', PostalCode::MAX_LENGTH + 1));
    }
}
