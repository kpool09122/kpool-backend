<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticatedAccountSummaryReadModel
{
    /** @param array<string, mixed>|null $address */
    public function __construct(
        private string $accountIdentifier,
        private string $email,
        private string $type,
        private string $name,
        private string $status,
        private string $accountCategory,
        private ?string $phone,
        private ?array $address,
    ) {
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
