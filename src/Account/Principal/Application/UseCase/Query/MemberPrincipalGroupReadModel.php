<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query;

readonly class MemberPrincipalGroupReadModel
{
    public function __construct(
        private string $principalGroupIdentifier,
        private string $name,
        private bool $isDefault,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'principalGroupIdentifier' => $this->principalGroupIdentifier,
            'name' => $this->name,
            'isDefault' => $this->isDefault,
        ];
    }
}
