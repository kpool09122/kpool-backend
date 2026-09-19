<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

readonly class VerifiedPasskey
{
    /** @param string[] $transports */
    public function __construct(
        private string $credentialId,
        private string $credentialSource,
        private int $signCount,
        private bool $backupEligible,
        private bool $backupState,
        private array $transports,
    ) {
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    public function credentialSource(): string
    {
        return $this->credentialSource;
    }

    public function signCount(): int
    {
        return $this->signCount;
    }

    public function backupEligible(): bool
    {
        return $this->backupEligible;
    }

    public function backupState(): bool
    {
        return $this->backupState;
    }

    /** @return string[] */
    public function transports(): array
    {
        return $this->transports;
    }
}
