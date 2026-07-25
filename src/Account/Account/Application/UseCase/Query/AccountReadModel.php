<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountReadModel
{
    public function __construct(
        private string $accountIdentifier,
        private string $email,
        private string $type,
        private string $name,
        private string $status,
        private string $accountCategory,
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

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'accountIdentifier' => $this->accountIdentifier,
            'email' => $this->email,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->status,
            'accountCategory' => $this->accountCategory,
        ];
    }
}
