<?php

declare(strict_types=1);

namespace Identity\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Identity\Domain\Factory\AuthCodeSessionFactoryInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Infrastructure\Factory\AuthCodeSessionFactory;
use Source\Shared\Domain\ValueObject\Email;
use Tests\TestCase;

class AuthCodeSessionFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AuthCodeSessionFactoryInterface::class);
        $this->assertInstanceOf(AuthCodeSessionFactory::class, $factory);
    }

    /**
     * 正常系: AuthCodeSession Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws \DateMalformedStringException
     */
    public function testCreate(): void
    {
        $email = new Email('user@example.com');
        $authCode = new AuthCode('123456');
        $before = new DateTimeImmutable('now');

        $factory = $this->app->make(AuthCodeSessionFactoryInterface::class);
        $session = $factory->create($email, $authCode);
        $after = new DateTimeImmutable('now');

        $this->assertSame($email, $session->email());
        $this->assertSame($authCode, $session->authCode());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $session->generatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $session->generatedAt()->getTimestamp());
        $this->assertEquals($session->generatedAt()->modify('+15 minutes'), $session->expiresAt());
        $this->assertEquals($session->generatedAt()->modify('+1 minute'), $session->retryableAt());
    }
}
