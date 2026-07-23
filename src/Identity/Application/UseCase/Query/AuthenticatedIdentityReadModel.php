<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticatedIdentityReadModel
{
    public function __construct(
        private string $identityIdentifier,
        private string $identityName,
        private string $email,
        private string $language,
        private ?string $profileImage,
        private ?string $accountIdentifier,
        private ?string $accountRole,
    ) {
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

    public function language(): string
    {
        return $this->language;
    }

    public function profileImage(): ?string
    {
        return $this->profileImage;
    }

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function accountRole(): ?string
    {
        return $this->accountRole;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identityIdentifier' => $this->identityIdentifier,
            'identityName' => $this->identityName,
            'email' => $this->email,
            'language' => $this->language,
            'profileImage' => $this->profileImage,
            'accountIdentifier' => $this->accountIdentifier,
            'accountRole' => $this->accountRole,
        ];
    }
}
