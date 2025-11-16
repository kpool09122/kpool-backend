<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

readonly class AuthCodeSession
{
    private DateTimeImmutable $expiresAt;
    private DateTimeImmutable $retryableAt;

    public function __construct(
        private Email                  $email,
        private AuthCode               $authCode,
        private DateTimeImmutable      $generatedAt,
        private ?DateTimeImmutable     $verifiedAt = null,
    ) {
        $this->expiresAt = $this->generatedAt->modify('+15 minutes');
        $this->retryableAt = $this->generatedAt->modify('+1 minute');
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function authCode(): AuthCode
    {
        return $this->authCode;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function retryableAt(): DateTimeImmutable
    {
        return $this->retryableAt;
    }

    public function verifiedAt(): ?DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function checkNotExpired(DateTimeImmutable $now): void
    {
        if ($this->expiresAt <= $now) {
            throw new DomainException('認証コードの有効期限が切れています。');
        }
    }

    public function matchAuthCode(AuthCode $authCode): void
    {
        if ((string)$this->authCode !== (string)$authCode) {
            throw new DomainException('認証コードが一致しません。');
        }
    }
}
