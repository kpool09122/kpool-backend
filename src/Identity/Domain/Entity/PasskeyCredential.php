<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Identity\Domain\Exception\InvalidPasskeyBackupStateException;
use Source\Identity\Domain\Exception\PasskeyBackupEligibilityChangedException;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

class PasskeyCredential
{
    private const array ALLOWED_TRANSPORTS = ['ble', 'hybrid', 'internal', 'nfc', 'smart-card', 'usb'];

    /** @param string[] $transports */
    public function __construct(
        private readonly PasskeyCredentialIdentifier $identifier,
        private readonly PasskeyUserIdentifier $passkeyUserIdentifier,
        private readonly WebAuthnCredentialId $credentialId,
        private CredentialSource $credentialSource,
        private int $signCount,
        private readonly bool $backupEligible,
        private bool $backupState,
        private readonly array $transports,
        private PasskeyDisplayName $displayName,
        private ?DateTimeImmutable $lastUsedAt,
    ) {
        if ($signCount < 0) {
            throw new InvalidArgumentException('Sign count cannot be negative.');
        }
        if (! $backupEligible && $backupState) {
            throw new InvalidPasskeyBackupStateException('A non-backup-eligible credential cannot be backed up.');
        }
        foreach ($transports as $transport) {
            if (! in_array($transport, self::ALLOWED_TRANSPORTS, true)) {
                throw new InvalidArgumentException('Unsupported authenticator transport.');
            }
        }
        if (count($transports) !== count(array_unique($transports))) {
            throw new InvalidArgumentException('Authenticator transports must be unique.');
        }
    }

    public function identifier(): PasskeyCredentialIdentifier
    {
        return $this->identifier;
    }

    public function passkeyUserIdentifier(): PasskeyUserIdentifier
    {
        return $this->passkeyUserIdentifier;
    }

    public function credentialId(): WebAuthnCredentialId
    {
        return $this->credentialId;
    }

    public function credentialSource(): CredentialSource
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

    public function displayName(): PasskeyDisplayName
    {
        return $this->displayName;
    }

    public function lastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function rename(PasskeyDisplayName $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function recordAuthentication(
        CredentialSource $credentialSource,
        int $signCount,
        bool $backupEligible,
        bool $backupState,
        DateTimeImmutable $usedAt,
    ): void {
        if ($backupEligible !== $this->backupEligible) {
            throw new PasskeyBackupEligibilityChangedException('Backup eligibility is immutable.');
        }
        if (! $backupEligible && $backupState) {
            throw new InvalidPasskeyBackupStateException('A non-backup-eligible credential cannot be backed up.');
        }
        if ($signCount < 0) {
            throw new InvalidArgumentException('Sign count cannot be negative.');
        }

        $this->credentialSource = $credentialSource;
        $this->signCount = $signCount;
        $this->backupState = $backupState;
        $this->lastUsedAt = $usedAt;
    }
}
