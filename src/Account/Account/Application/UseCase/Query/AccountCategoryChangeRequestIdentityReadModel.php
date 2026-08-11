<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountCategoryChangeRequestIdentityReadModel
{
    public function __construct(
        private string $name,
        private string $email,
    ) {
    }

    /** @return array{name: string, email: string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
