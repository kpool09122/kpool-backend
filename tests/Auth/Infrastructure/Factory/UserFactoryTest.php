<?php

declare(strict_types=1);

namespace Tests\Auth\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProfile;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Auth\Infrastructure\Factory\UserFactory;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
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
        $language = Language::JAPANESE;
        $plainPassword = new PlainPassword('user-password');
        $userFactory = $this->app->make(UserFactoryInterface::class);
        $user = $userFactory->create($name, $email, $language, $plainPassword);
        $this->assertTrue(UlidValidator::isValid((string)$user->userIdentifier()));
        $this->assertSame((string)$email, (string)$user->email());
        $this->assertSame($language, $user->language());
        $this->assertTrue(password_verify((string) $plainPassword, (string) $user->hashedPassword()));
        $this->assertSame([], $user->serviceRoles());
        $this->assertNull($user->emailVerifiedAt());
    }

    /**
     * 正常系: ソーシャルプロフィールから正しくユーザーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateFromSocialProfile(): void
    {
        $provider = SocialProvider::GOOGLE;
        $providerUserId = 'google-user-1';
        $email = new Email('user@example.com');
        $name = 'Google User';
        $avatarUrl = 'https://example.com/avatar.png';
        $profile = new SocialProfile($provider, $providerUserId, $email, $name, $avatarUrl);

        $userFactory = $this->app->make(UserFactoryInterface::class);

        $user = $userFactory->createFromSocialProfile($profile);

        $this->assertTrue(UlidValidator::isValid((string)$user->userIdentifier()));
        $this->assertSame((string)$email, (string)$user->email());
        $this->assertSame(Language::ENGLISH, $user->language());
        $this->assertSame($name, (string)$user->userName());
        $this->assertSame($avatarUrl, (string)$user->profileImage());
        $this->assertSame([], $user->serviceRoles());
        $this->assertNull($user->emailVerifiedAt());
        $this->assertTrue($user->hasSocialConnection(new SocialConnection($provider, $providerUserId)));
        $this->assertNotEmpty((string)$user->hashedPassword());
    }

    /**
     * 正常系: ソーシャルプロフィールの名前が空の時は、メールアドレスの@より前の部分がユーザー名に利用されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateFromSocialProfileWhenNameIsEmpty(): void
    {
        $provider = SocialProvider::GOOGLE;
        $providerUserId = 'google-user-1';
        $email = new Email('user@example.com');
        $name = '';
        $avatarUrl = null;
        $profile = new SocialProfile($provider, $providerUserId, $email, $name, $avatarUrl);

        $userFactory = $this->app->make(UserFactoryInterface::class);

        $user = $userFactory->createFromSocialProfile($profile);

        $this->assertSame('user', (string)$user->userName());
        $this->assertNull($user->profileImage());
    }
}
