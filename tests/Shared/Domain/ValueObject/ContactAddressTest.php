<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ContactAddress;

class ContactAddressTest extends TestCase
{
    public function testFromArrayCreatesNullableContactAddress(): void
    {
        $address = ContactAddress::fromArray([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
            'postalCode' => '100-0001',
            'locality' => '千代田区',
            'addressLine1' => '千代田1-1',
            'addressLine2' => null,
        ]);

        $this->assertSame([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
            'postalCode' => '100-0001',
            'locality' => '千代田区',
            'addressLine1' => '千代田1-1',
            'addressLine2' => null,
        ], $address->toArray());
    }

    public function testAdministrativeAreaCodeRequiresCountryCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Administrative area code must be null when country code is null.');

        ContactAddress::fromArray([
            'countryCode' => null,
            'administrativeAreaCode' => 'CA',
            'addressLine1' => '1 Market St',
        ]);
    }

    public function testAdministrativeAreaCodeMustMatchCountryCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Administrative area code is invalid for country code.');

        ContactAddress::fromArray([
            'countryCode' => 'JP',
            'administrativeAreaCode' => 'FL',
            'addressLine1' => '10 Rue Example',
        ]);
    }

    public function testFromArrayAllowsUnitedStatesAdministrativeAreaCode(): void
    {
        $address = ContactAddress::fromArray([
            'countryCode' => 'US',
            'administrativeAreaCode' => 'FL',
            'addressLine1' => '1 Ocean Dr',
        ]);

        $this->assertSame([
            'countryCode' => 'US',
            'administrativeAreaCode' => 'FL',
            'postalCode' => null,
            'locality' => null,
            'addressLine1' => '1 Ocean Dr',
            'addressLine2' => null,
        ], $address->toArray());
    }
}
