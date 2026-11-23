<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Account\Account\Domain\Entity\Account;

readonly class AccountSnapshotProfile
{
    public function __construct(
        private string $email,
        private string $type,
        private string $name,
        private string $status,
        private string $phone,
        private string $countryCode,
        private ?string $administrativeAreaCode,
        private string $postalCode,
        private ?string $locality,
        private string $addressLine1,
        private ?string $addressLine2,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function administrativeAreaCode(): ?string
    {
        return $this->administrativeAreaCode;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function locality(): ?string
    {
        return $this->locality;
    }

    public function addressLine1(): string
    {
        return $this->addressLine1;
    }

    public function addressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public static function fromAccount(Account $account): self
    {
        $address = $account->address() ?? throw new InvalidArgumentException('Account address is required.');

        return new self(
            email: (string) $account->email(),
            type: ($account->type() ?? throw new InvalidArgumentException('Account type is required.'))->value,
            name: (string) $account->name(),
            status: $account->status()->value,
            phone: (string) ($account->phone() ?? throw new InvalidArgumentException('Account phone is required.')),
            countryCode: ($address->countryCode() ?? throw new InvalidArgumentException('Account country is required.'))->value,
            administrativeAreaCode: $address->administrativeAreaCode()?->code(),
            postalCode: (string) ($address->postalCode() ?? throw new InvalidArgumentException('Account postal code is required.')),
            locality: $address->locality() !== null ? (string) $address->locality() : null,
            addressLine1: (string) ($address->addressLine1() ?? throw new InvalidArgumentException('Account address line 1 is required.')),
            addressLine2: $address->addressLine2() !== null ? (string) $address->addressLine2() : null,
        );
    }
}
