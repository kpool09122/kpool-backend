<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

class AuthCodeSessionTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     * @throws \DateMalformedStringException
     */
    public function test__construct(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');

        $session = new AuthCodeSession($email, $authCode, $generatedAt);

        $this->assertSame($email, $session->email());
        $this->assertSame($authCode, $session->authCode());
        $this->assertEquals(
            $generatedAt->modify('+15 minutes'),
            $session->expiresAt()
        );
        $this->assertSame($generatedAt, $session->generatedAt());
        $this->assertEquals(
            $generatedAt->modify('+1 minute'),
            $session->retryableAt()
        );
    }
}
