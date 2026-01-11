<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\CountryCode;

readonly class BillingAddress
{
    public function __construct(
        private CountryCode $countryCode,
        private PostalCode $postalCode,
        private StateOrProvince $stateOrProvince,
        private City $city,
        private AddressLine $addressLine1,
        private ?AddressLine $addressLine2 = null,
        private ?AddressLine $addressLine3 = null,
    ) {
    }

    public function countryCode(): CountryCode
    {
        return $this->countryCode;
    }

    public function postalCode(): PostalCode
    {
        return $this->postalCode;
    }

    public function stateOrProvince(): StateOrProvince
    {
        return $this->stateOrProvince;
    }

    public function city(): City
    {
        return $this->city;
    }

    public function addressLine1(): AddressLine
    {
        return $this->addressLine1;
    }

    public function addressLine2(): ?AddressLine
    {
        return $this->addressLine2;
    }

    public function addressLine3(): ?AddressLine
    {
        return $this->addressLine3;
    }
}
