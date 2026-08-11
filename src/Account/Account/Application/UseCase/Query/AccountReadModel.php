<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountReadModel
{
    /** @param array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string, addressLine2: string|null}|null $address */
    public function __construct(
        private string $accountIdentifier,
        private string $email,
        private string $type,
        private string $name,
        private string $status,
        private string $accountCategory,
        private ?string $phone = null,
        private ?array $address = null,
    ) {
    }

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
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

    public function accountCategory(): string
    {
        return $this->accountCategory;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    /** @return array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string, addressLine2: string|null}|null */
    public function address(): ?array
    {
        return $this->address;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'accountIdentifier' => $this->accountIdentifier,
            'email' => $this->email,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->status,
            'accountCategory' => $this->accountCategory,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}
