<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticatedIdentityReadModel
{
    public function __construct(
        private string $identityIdentifier,
        private string $username,
        private string $email,
        private string $language,
        private ?string $profileImage,
    ) {
    }

    public function identityIdentifier(): string
    {
        return $this->identityIdentifier;
    }

    public function username(): string
    {
        return $this->username;
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identityIdentifier' => $this->identityIdentifier,
            'username' => $this->username,
            'email' => $this->email,
            'language' => $this->language,
            'profileImage' => $this->profileImage,
        ];
    }
}
