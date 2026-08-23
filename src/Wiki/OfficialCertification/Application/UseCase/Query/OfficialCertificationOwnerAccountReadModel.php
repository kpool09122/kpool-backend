<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query;

readonly class OfficialCertificationOwnerAccountReadModel
{
    public function __construct(
        private string $accountIdentifier,
        private string $email,
        private string $type,
        private string $name,
        private string $status,
        private string $category,
    ) {
    }

    /**
     * @return array{accountIdentifier: string, email: string, type: string, name: string, status: string, category: string}
     */
    public function toArray(): array
    {
        return [
            'accountIdentifier' => $this->accountIdentifier,
            'email' => $this->email,
            'type' => $this->type,
            'name' => $this->name,
            'status' => $this->status,
            'category' => $this->category,
        ];
    }
}
