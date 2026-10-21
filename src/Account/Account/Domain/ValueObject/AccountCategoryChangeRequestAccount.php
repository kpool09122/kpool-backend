<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Account\Account\Domain\Entity\Account;

readonly class AccountCategoryChangeRequestAccount
{
    public function __construct(
        public string $email,
        public string $type,
        public string $name,
        public string $status,
        public string $phone,
        public string $countryCode,
        public ?string $administrativeAreaCode,
        public string $postalCode,
        public ?string $locality,
        public string $addressLine1,
        public ?string $addressLine2,
    ) {
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
