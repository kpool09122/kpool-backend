<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class PasskeyReadModel
{
    /** @param string[] $transports */
    public function __construct(
        private string $passkeyIdentifier,
        private string $displayName,
        private array $transports,
        private bool $backupEligible,
        private bool $backupState,
        private ?string $lastUsedAt,
        private string $createdAt,
    ) {
    }

    public function passkeyIdentifier(): string
    {
        return $this->passkeyIdentifier;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    /** @return string[] */
    public function transports(): array
    {
        return $this->transports;
    }

    public function backupEligible(): bool
    {
        return $this->backupEligible;
    }

    public function backupState(): bool
    {
        return $this->backupState;
    }

    public function lastUsedAt(): ?string
    {
        return $this->lastUsedAt;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'passkeyIdentifier' => $this->passkeyIdentifier,
            'displayName' => $this->displayName,
            'transports' => $this->transports,
            'backupEligible' => $this->backupEligible,
            'backupState' => $this->backupState,
            'lastUsedAt' => $this->lastUsedAt,
            'createdAt' => $this->createdAt,
        ];
    }
}
