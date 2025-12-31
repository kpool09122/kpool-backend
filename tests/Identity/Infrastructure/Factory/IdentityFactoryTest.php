<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Identity\Infrastructure\Factory\IdentityFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class IdentityFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $identityFactory = $this->app->make(IdentityFactoryInterface::class);
        $this->assertInstanceOf(IdentityFactory::class, $identityFactory);
    }

    /**
     * 正常系: Identity Entityが正しく作成されること.
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
        $identityFactory = $this->app->make(IdentityFactoryInterface::class);
        $identity = $identityFactory->create($name, $email, $language, $plainPassword);
        $this->assertTrue(UuidValidator::isValid((string)$identity->identityIdentifier()));
        $this->assertSame((string)$email, (string)$identity->email());
        $this->assertSame($language, $identity->language());
        $this->assertTrue(password_verify((string) $plainPassword, (string) $identity->hashedPassword()));
        $this->assertNull($identity->emailVerifiedAt());
    }

    /**
     * 正常系: ソーシャルプロフィールから正しくIdentityを作成できること.
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

        $identityFactory = $this->app->make(IdentityFactoryInterface::class);

        $identity = $identityFactory->createFromSocialProfile($profile);

        $this->assertTrue(UuidValidator::isValid((string)$identity->identityIdentifier()));
        $this->assertSame((string)$email, (string)$identity->email());
        $this->assertSame(Language::ENGLISH, $identity->language());
        $this->assertSame($name, (string)$identity->username());
        $this->assertSame($avatarUrl, (string)$identity->profileImage());
        $this->assertNull($identity->emailVerifiedAt());
        $this->assertTrue($identity->hasSocialConnection(new SocialConnection($provider, $providerUserId)));
        $this->assertNotEmpty((string)$identity->hashedPassword());
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

        $identityFactory = $this->app->make(IdentityFactoryInterface::class);

        $identity = $identityFactory->createFromSocialProfile($profile);

        $this->assertSame('user', (string)$identity->username());
        $this->assertNull($identity->profileImage());
    }
}
