<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;

readonly class ContactAddress
{
    /** @var array<string, bool> */
    private const array ADMINISTRATIVE_AREA_CODE_COUNTRIES = [
        'JP' => true,
        'KR' => true,
        'US' => true,
    ];

    public function __construct(
        private ?CountryCode $countryCode,
        private ?AdministrativeAreaCode $administrativeAreaCode,
        private ?PostalCode $postalCode,
        private ?Locality $locality,
        private AddressLine $addressLine1,
        private ?AddressLine $addressLine2 = null,
    ) {
        if ($this->countryCode === null && $this->administrativeAreaCode !== null) {
            throw new InvalidArgumentException('Administrative area code must be null when country code is null.');
        }

        if (
            $this->countryCode !== null
            && $this->administrativeAreaCode !== null
            && ! isset(self::ADMINISTRATIVE_AREA_CODE_COUNTRIES[$this->countryCode->value])
        ) {
            throw new InvalidArgumentException('Administrative area code is supported only for configured countries.');
        }
    }

    public function countryCode(): ?CountryCode
    {
        return $this->countryCode;
    }

    public function administrativeAreaCode(): ?AdministrativeAreaCode
    {
        return $this->administrativeAreaCode;
    }

    public function postalCode(): ?PostalCode
    {
        return $this->postalCode;
    }

    public function locality(): ?Locality
    {
        return $this->locality;
    }

    public function addressLine1(): AddressLine
    {
        return $this->addressLine1;
    }

    public function addressLine2(): ?AddressLine
    {
        return $this->addressLine2;
    }

    /**
     * @param array{countryCode?: string|null, administrativeAreaCode?: string|null, postalCode?: string|null, locality?: string|null, addressLine1: string, addressLine2?: string|null} $value
     */
    public static function fromArray(array $value): self
    {
        return new self(
            countryCode: array_key_exists('countryCode', $value) && $value['countryCode'] !== null ? CountryCode::from($value['countryCode']) : null,
            administrativeAreaCode: array_key_exists('administrativeAreaCode', $value) && $value['administrativeAreaCode'] !== null ? new AdministrativeAreaCode($value['administrativeAreaCode']) : null,
            postalCode: array_key_exists('postalCode', $value) && $value['postalCode'] !== null ? new PostalCode($value['postalCode']) : null,
            locality: array_key_exists('locality', $value) && $value['locality'] !== null ? new Locality($value['locality']) : null,
            addressLine1: new AddressLine($value['addressLine1']),
            addressLine2: array_key_exists('addressLine2', $value) && $value['addressLine2'] !== null ? new AddressLine($value['addressLine2']) : null,
        );
    }

    /** @return array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string, addressLine2: string|null} */
    public function toArray(): array
    {
        return [
            'countryCode' => $this->countryCode?->value,
            'administrativeAreaCode' => $this->administrativeAreaCode !== null ? (string) $this->administrativeAreaCode : null,
            'postalCode' => $this->postalCode !== null ? (string) $this->postalCode : null,
            'locality' => $this->locality !== null ? (string) $this->locality : null,
            'addressLine1' => (string) $this->addressLine1,
            'addressLine2' => $this->addressLine2 !== null ? (string) $this->addressLine2 : null,
        ];
    }
}
