<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\AddressLine;
use Source\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Domain\ValueObject\City;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;

class BillingAddressTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     */
    public function test__construct(): void
    {
        $countryCode = CountryCode::JAPAN;
        $postalCode = new PostalCode('100-0001');
        $stateOrProvince = new StateOrProvince('Tokyo');
        $city = new City('Chiyoda');
        $addressLine1 = new AddressLine('1-1-1');
        $addressLine2 = new AddressLine('Chiyoda Building 2F');
        $addressLine3 = new AddressLine('Room 123');

        $billingAddress = new BillingAddress(
            countryCode: $countryCode,
            postalCode: $postalCode,
            stateOrProvince: $stateOrProvince,
            city: $city,
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            addressLine3: $addressLine3,
        );

        $this->assertSame($countryCode, $billingAddress->countryCode());
        $this->assertSame($postalCode, $billingAddress->postalCode());
        $this->assertSame($stateOrProvince, $billingAddress->stateOrProvince());
        $this->assertSame($city, $billingAddress->city());
        $this->assertSame($addressLine1, $billingAddress->addressLine1());
        $this->assertSame($addressLine2, $billingAddress->addressLine2());
        $this->assertSame($addressLine3, $billingAddress->addressLine3());
    }
}
