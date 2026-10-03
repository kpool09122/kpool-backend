<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AdministrativeAreaCode;
use Source\Shared\Domain\ValueObject\CountryCode;

class AdministrativeAreaCodeTest extends TestCase
{
    public function testCountryAndCode(): void
    {
        $area = AdministrativeAreaCode::UNITED_STATES_CALIFORNIA;
        $this->assertSame(CountryCode::UNITED_STATES, $area->countryCode());
        $this->assertSame('CA', $area->code());
        $this->assertTrue($area->isSupportedBy(CountryCode::UNITED_STATES));
        $this->assertFalse($area->isSupportedBy(CountryCode::JAPAN));
        $this->assertSame($area, AdministrativeAreaCode::fromCountryAndCode(CountryCode::UNITED_STATES, ' ca '));
        $this->assertSame(AdministrativeAreaCode::JAPAN_TOKYO, AdministrativeAreaCode::fromCountryAndCode(CountryCode::JAPAN, '13'));
    }

    public function testEveryAreaHasMatchingCountryAndCanBeRestored(): void
    {
        foreach (AdministrativeAreaCode::cases() as $area) {
            $this->assertSame($area, AdministrativeAreaCode::fromCountryAndCode($area->countryCode(), $area->code()));
            $this->assertSame($area->countryCode()->value . '-' . $area->code(), $area->value);
        }
    }

    public function testUnsupportedCombinationReturnsNull(): void
    {
        $this->assertNull(AdministrativeAreaCode::tryFromCountryAndCode(CountryCode::JAPAN, 'CA'));
    }

    public function testUnsupportedCombinationThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AdministrativeAreaCode::fromCountryAndCode(CountryCode::JAPAN, 'CA');
    }
}
