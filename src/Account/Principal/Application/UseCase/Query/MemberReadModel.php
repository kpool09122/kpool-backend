<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query;

readonly class MemberReadModel
{
    /** @param array<int, MemberPrincipalGroupReadModel> $principalGroups */
    public function __construct(
        private string $principalIdentifier,
        private string $identityIdentifier,
        private string $identityName,
        private string $email,
        private array $principalGroups,
    ) {
    }

    public function principalIdentifier(): string
    {
        return $this->principalIdentifier;
    }

    public function identityIdentifier(): string
    {
        return $this->identityIdentifier;
    }

    public function identityName(): string
    {
        return $this->identityName;
    }

    public function email(): string
    {
        return $this->email;
    }

    /** @return array<int, MemberPrincipalGroupReadModel> */
    public function principalGroups(): array
    {
        return $this->principalGroups;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'principalIdentifier' => $this->principalIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'identityName' => $this->identityName,
            'email' => $this->email,
            'principalGroups' => array_map(static fn (MemberPrincipalGroupReadModel $principalGroup): array => $principalGroup->toArray(), $this->principalGroups),
        ];
    }
}
