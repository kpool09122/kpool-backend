<?php

declare(strict_types=1);

namespace Tests\Auth\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Auth\Infrastructure\Factory\UserFactory;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Translation;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $userFactory = $this->app->make(UserFactoryInterface::class);
        $this->assertInstanceOf(UserFactory::class, $userFactory);
    }

    /**
     * 正常系: User Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = new UserName('user-name');
        $email = new Email('user@example.com');
        $translation = Translation::JAPANESE;
        $plainPassword = new PlainPassword('user-password');
        $userFactory = $this->app->make(UserFactoryInterface::class);
        $user = $userFactory->create($name, $email, $translation, $plainPassword);
        $this->assertTrue(UlidValidator::isValid((string)$user->userIdentifier()));
        $this->assertSame((string)$email, (string)$user->email());
        $this->assertSame($translation, $user->translation());
        $this->assertTrue(password_verify((string) $plainPassword, (string) $user->hashedPassword()));
        $this->assertSame([], $user->serviceRoles());
        $this->assertNull($user->emailVerifiedAt());
    }
}
