<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

class AuthCodeSessionFactory implements AuthCodeSessionFactoryInterface
{
    public function create(
        Email $email,
        AuthCode $authCode,
        ?DateTimeImmutable $verifiedAt = null,
    ): AuthCodeSession {
        return new AuthCodeSession(
            $email,
            $authCode,
            new DateTimeImmutable('now'),
            $verifiedAt,
        );
    }
}
