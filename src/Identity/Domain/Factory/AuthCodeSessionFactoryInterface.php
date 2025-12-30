<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeSessionFactoryInterface
{
    public function create(
        Email $email,
        AuthCode $authCode,
        ?DateTimeImmutable $verifiedAt = null,
    ): AuthCodeSession;
}
