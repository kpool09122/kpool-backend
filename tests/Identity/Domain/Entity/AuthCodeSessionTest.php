<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\InvalidAuthCodeException;
use Source\Identity\Domain\ValueObject\AuthCode;
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
        $this->assertNull($session->verifiedAt());
    }

    /**
     * 正常系: verifiedAtを指定してインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithVerifiedAt(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $generatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $verifiedAt = new DateTimeImmutable('2024-01-01T00:05:00+00:00');

        $session = new AuthCodeSession($email, $authCode, $generatedAt, $verifiedAt);

        $this->assertSame($verifiedAt, $session->verifiedAt());
    }

    /**
     * 正常系: 有効期限内であれば例外がスローされないこと.
     *
     * @return void
     * @throws AuthCodeExpiredException
     * @throws \DateMalformedStringException
     */
    public function testCheckNotExpiredPassesWhenNotExpired(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $session = new AuthCodeSession(
            new Email('user@example.com'),
            new AuthCode('123456'),
            $generatedAt,
        );

        $now = $generatedAt->modify('+10 minutes');
        $session->checkNotExpired($now);

        $this->addToAssertionCount(1);
    }

    /**
     * 異常系: 有効期限切れの場合、AuthCodeExpiredExceptionがスローされること.
     *
     * @return void
     * @throws \DateMalformedStringException
     */
    public function testCheckNotExpiredThrowsWhenExpired(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $session = new AuthCodeSession(
            new Email('user@example.com'),
            new AuthCode('123456'),
            $generatedAt,
        );

        $now = $generatedAt->modify('+16 minutes');

        $this->expectException(AuthCodeExpiredException::class);
        $this->expectExceptionMessage('認証コードの有効期限が切れています。');

        $session->checkNotExpired($now);
    }

    /**
     * 異常系: ちょうど有効期限の場合、AuthCodeExpiredExceptionがスローされること.
     *
     * @return void
     * @throws \DateMalformedStringException
     */
    public function testCheckNotExpiredThrowsWhenExactlyExpired(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $session = new AuthCodeSession(
            new Email('user@example.com'),
            new AuthCode('123456'),
            $generatedAt,
        );

        $now = $generatedAt->modify('+15 minutes');

        $this->expectException(AuthCodeExpiredException::class);

        $session->checkNotExpired($now);
    }

    /**
     * 正常系: 認証コードが一致する場合、例外がスローされないこと.
     *
     * @return void
     * @throws InvalidAuthCodeException
     */
    public function testMatchAuthCodePassesWhenMatching(): void
    {
        $authCode = new AuthCode('123456');
        $session = new AuthCodeSession(
            new Email('user@example.com'),
            $authCode,
            new DateTimeImmutable(),
        );

        $session->matchAuthCode(new AuthCode('123456'));

        $this->addToAssertionCount(1);
    }

    /**
     * 異常系: 認証コードが一致しない場合、InvalidAuthCodeExceptionがスローされること.
     *
     * @return void
     */
    public function testMatchAuthCodeThrowsWhenNotMatching(): void
    {
        $session = new AuthCodeSession(
            new Email('user@example.com'),
            new AuthCode('123456'),
            new DateTimeImmutable(),
        );

        $this->expectException(InvalidAuthCodeException::class);
        $this->expectExceptionMessage('認証コードが一致しません。');

        $session->matchAuthCode(new AuthCode('654321'));
    }
}
