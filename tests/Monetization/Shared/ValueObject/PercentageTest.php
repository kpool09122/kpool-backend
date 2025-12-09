<?php

declare(strict_types=1);

namespace Tests\Monetization\Shared\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Shared\ValueObject\Percentage;

class PercentageTest extends TestCase
{
    public function testHoldsValue(): void
    {
        $percentage = new Percentage(15);

        $this->assertSame(15, $percentage->value());
    }

    public function testRejectsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Percentage(101);
    }
}
