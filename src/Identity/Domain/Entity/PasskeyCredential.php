<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyCredential
{
    /** @param string[] $transports */
    public function __construct(
        private readonly string $identifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private readonly string $credentialId,
        private string $credentialSource,
        private int $signCount,
        private readonly bool $backupEligible,
        private bool $backupState,
        private readonly array $transports,
        private string $displayName,
        private ?DateTimeImmutable $lastUsedAt = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
        if ($identifier === '' || $credentialId === '' || $credentialSource === '') {
            throw new InvalidPasskeyException('パスキー資格情報が不正です');
        }
        if ($displayName === '' || mb_strlen($displayName) > 100) {
            throw new InvalidPasskeyException('パスキーの表示名は1文字以上100文字以下で指定してください');
        }
        if (! $backupEligible && $backupState) {
            throw new InvalidPasskeyException('backup eligibilityが無効なパスキーをbackup状態にはできません');
        }
        if ($signCount < 0) {
            throw new InvalidPasskeyException('署名カウンターは0以上である必要があります');
        }
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
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

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function lastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rename(string $displayName): void
    {
        if ($displayName === '' || mb_strlen($displayName) > 100) {
            throw new InvalidPasskeyException('パスキーの表示名は1文字以上100文字以下で指定してください');
        }
        $this->displayName = $displayName;
    }

    public function recordAuthentication(
        string $credentialSource,
        int $signCount,
        bool $backupEligible,
        bool $backupState,
        DateTimeImmutable $usedAt,
    ): void {
        if ($backupEligible !== $this->backupEligible) {
            throw new InvalidPasskeyException('登録時からbackup eligibilityが変更されています');
        }
        if (! $backupEligible && $backupState) {
            throw new InvalidPasskeyException('backup eligibilityとbackup stateの組み合わせが不正です');
        }
        if ($signCount < 0) {
            throw new InvalidPasskeyException('署名カウンターは0以上である必要があります');
        }
        $this->credentialSource = $credentialSource;
        $this->signCount = $signCount;
        $this->backupState = $backupState;
        $this->lastUsedAt = $usedAt;
    }
}
