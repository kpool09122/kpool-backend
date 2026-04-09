<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifyEmail;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailOutput;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

class VerifyEmailOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyWhenSessionIsNull(): void
    {
        $output = new VerifyEmailOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsSessionData(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $verifiedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $session = new AuthCodeSession($email, $authCode, $verifiedAt, $verifiedAt);

        $output = new VerifyEmailOutput();
        $output->setSession($session);

        $result = $output->toArray();

        $this->assertSame((string) $email, $result['email']);
        $this->assertSame($verifiedAt->format(DateTimeInterface::ATOM), $result['verifiedAt']);
    }
}
