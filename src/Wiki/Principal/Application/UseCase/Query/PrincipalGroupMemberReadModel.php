<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query;

readonly class PrincipalGroupMemberReadModel
{
    public function __construct(
        private string $principalIdentifier,
        private string $identityIdentifier,
        private string $identityName,
        private string $email,
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

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'principalIdentifier' => $this->principalIdentifier,
            'identityIdentifier' => $this->identityIdentifier,
            'identityName' => $this->identityName,
            'email' => $this->email,
        ];
    }
}
