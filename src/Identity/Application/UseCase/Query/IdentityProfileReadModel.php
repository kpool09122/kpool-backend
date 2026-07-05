<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class IdentityProfileReadModel
{
    public function __construct(
        private string $identityIdentifier,
        private string $identityName,
        private string $language,
        private ?string $profileImage,
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
            'identityName' => $this->identityName,
            'language' => $this->language,
            'profileImage' => $this->profileImage,
        ];
    }
}
