<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Entity;

use DateTimeImmutable;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

readonly class AuthCodeSession
{
    private DateTimeImmutable $expiresAt;
    private DateTimeImmutable $retryableAt;

    public function __construct(
        private Email             $email,
        private AuthCode          $authCode,
        private DateTimeImmutable $generatedAt,
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
}
