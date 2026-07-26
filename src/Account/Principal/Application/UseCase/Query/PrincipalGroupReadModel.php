<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query;

readonly class PrincipalGroupReadModel
{
    /**
     * @param array<int, string> $roleIdentifiers
     * @param array<int, string> $members
     */
    public function __construct(
        private string $principalGroupIdentifier,
        private string $accountIdentifier,
        private string $name,
        private array $roleIdentifiers,
        private bool $isDefault,
        private array $members,
    ) {
    }

    public function principalGroupIdentifier(): string
    {
        return $this->principalGroupIdentifier;
    }

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return array<int, string> */
    public function roleIdentifiers(): array
    {
        return $this->roleIdentifiers;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /** @return array<int, string> */
    public function members(): array
    {
        return $this->members;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'principalGroupIdentifier' => $this->principalGroupIdentifier,
            'accountIdentifier' => $this->accountIdentifier,
            'name' => $this->name,
            'roleIdentifiers' => $this->roleIdentifiers,
            'isDefault' => $this->isDefault,
            'members' => $this->members,
        ];
    }
}
