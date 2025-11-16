<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Factory;

use DateTimeImmutable;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSessionFactoryInterface
{
    public function create(
        Email $email,
        AuthCode $authCode,
        ?DateTimeImmutable $verifiedAt = null,
    ): AuthCodeSession;
}
