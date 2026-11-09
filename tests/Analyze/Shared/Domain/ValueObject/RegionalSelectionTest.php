<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Shared\Domain\ValueObject\CountryCode;

class RegionalSelectionTest extends TestCase
{
    public function testNullCountryScopeAndUnknownRegionAreDifferent(): void
    {
        self::assertNull((new RegionalSelection(CountryCode::JAPAN))->region);
        self::assertSame('', (new RegionalSelection(CountryCode::JAPAN, ''))->region);
    }

    public function testRegionMustMatchCountry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RegionalSelection(CountryCode::JAPAN, 'DE-BE');
    }
}
