<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Locality;

class LocalityTest extends TestCase
{
    public function testTrimsAndPreservesMaximumLength(): void
    {
        $value = str_repeat('あ', Locality::MAX_LENGTH);
        $this->assertSame($value, (string) new Locality('  ' . $value . '  '));
    }

    public function testRejectsBlankValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locality('   ');
    }

    public function testRejectsTooLongValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locality(str_repeat('あ', Locality::MAX_LENGTH + 1));
    }
}
