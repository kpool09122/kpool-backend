<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query;

readonly class MemberPrincipalGroupReadModel
{
    public function __construct(
        private string $principalGroupIdentifier,
        private string $name,
        private bool $isDefault,
        private ?string $description = null,
    ) {
    }

    /** @return array{principalGroupIdentifier: string, name: string, description: string|null, isDefault: bool} */
    public function toArray(): array
    {
        return [
            'principalGroupIdentifier' => $this->principalGroupIdentifier,
            'name' => $this->name,
            'description' => $this->description,
            'isDefault' => $this->isDefault,
        ];
    }
}
