<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticatedAccountReferenceReadModel
{
    public function __construct(
        private string $accountIdentifier,
        private string $name,
    ) {
    }

    /** @return array{accountIdentifier: string, name: string} */
    public function toArray(): array
    {
        return [
            'accountIdentifier' => $this->accountIdentifier,
            'name' => $this->name,
        ];
    }
}
