<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;

class VisitorLocationTest extends TestCase
{
    #[DataProvider('validLocations')]
    public function testRetainsCodes(?string $country, ?string $region, ?string $expectedRegion): void
    {
        $location = new VisitorLocation($country, $region);
        self::assertSame($country, $location->country());
        self::assertSame($expectedRegion, $location->region());
    }

    /** @return array<array{?string, ?string, ?string}> */
    public static function validLocations(): array
    {
        return [['JP', '01', '01'], ['US', 'CA', 'CA'], ['ZZ', 'NEW9', 'NEW9'], ['JP', null, null], [null, null, null], [null, '01', null], ['ZZ', 'ABCDEFGHIJKLM', 'ABCDEFGHIJKLM']];
    }

    #[DataProvider('invalidLocations')]
    public function testRejectsMalformedCodes(?string $country, ?string $region): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VisitorLocation($country, $region);
    }

    /** @return array<array{?string, ?string}> */
    public static function invalidLocations(): array
    {
        return [['jp', '01'], ['', null], ['JPN', null], ['1P', null], ['JP', ''], ['JP', 'JP-01'], ['JP', 'abcdefgh'], ['JP', 'ABCDEFGHIJKLMN'], ['JP', "01\n"], ['JP', '<script>']];
    }
}
