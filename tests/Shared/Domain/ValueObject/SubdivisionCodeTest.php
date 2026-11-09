<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

class SubdivisionCodeTest extends TestCase
{
    public function testCodeOutsideMapCoverageIsRetained(): void
    {
        $code = new SubdivisionCode(CountryCode::GERMANY, 'DE-BE');
        self::assertSame('DE-BE', (string) $code);
        self::assertFalse($code->mapSupported());
    }

    public function testMapCoverageIsIndependent(): void
    {
        self::assertTrue((new SubdivisionCode(CountryCode::JAPAN, 'JP-13'))->mapSupported());
    }

    public function testForeignCountryIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SubdivisionCode(CountryCode::JAPAN, 'US-CA');
    }

    public function testInvalidCodeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SubdivisionCode(CountryCode::JAPAN, 'JP-NOTREAL');
    }
}
