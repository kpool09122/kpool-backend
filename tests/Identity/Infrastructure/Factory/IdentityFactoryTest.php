<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Identity\Infrastructure\Factory\IdentityFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
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

    /**
     * 正常系: 委譲Identityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateDelegatedIdentity(): void
    {
        $originalIdentity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new UserName('original-user'),
            new Email('original@example.com'),
            Language::JAPANESE,
            new ImagePath('/resources/path/original.png'),
            HashedPassword::fromPlain(new PlainPassword('OriginalPass1!')),
            new \DateTimeImmutable(),
        );

        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());

        $identityFactory = $this->app->make(IdentityFactoryInterface::class);

        $delegatedIdentity = $identityFactory->createDelegatedIdentity($originalIdentity, $delegationIdentifier);

        // identityIdentifierは新しく生成される
        $this->assertTrue(UuidValidator::isValid((string)$delegatedIdentity->identityIdentifier()));
        $this->assertNotEquals(
            (string)$originalIdentity->identityIdentifier(),
            (string)$delegatedIdentity->identityIdentifier()
        );

        // 元のIdentityの情報がコピーされる
        $this->assertSame((string)$originalIdentity->username(), (string)$delegatedIdentity->username());
        $this->assertSame((string)$originalIdentity->email(), (string)$delegatedIdentity->email());
        $this->assertSame($originalIdentity->language(), $delegatedIdentity->language());
        $this->assertSame((string)$originalIdentity->profileImage(), (string)$delegatedIdentity->profileImage());

        // 委譲関連のプロパティが正しく設定される
        $this->assertTrue($delegatedIdentity->isDelegatedIdentity());
        $this->assertSame((string)$delegationIdentifier, (string)$delegatedIdentity->delegationIdentifier());
        $this->assertSame(
            (string)$originalIdentity->identityIdentifier(),
            (string)$delegatedIdentity->originalIdentityIdentifier()
        );

        // その他のプロパティ
        $this->assertNull($delegatedIdentity->emailVerifiedAt());
        $this->assertEmpty($delegatedIdentity->socialConnections());
        $this->assertNotEmpty((string)$delegatedIdentity->hashedPassword());
    }

    /**
     * 正常系: プロフィール画像がnullの場合も委譲Identityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateDelegatedIdentityWithoutProfileImage(): void
    {
        $originalIdentity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new UserName('original-user'),
            new Email('original@example.com'),
            Language::KOREAN,
            null,
            HashedPassword::fromPlain(new PlainPassword('OriginalPass1!')),
            null,
        );

        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());

        $identityFactory = $this->app->make(IdentityFactoryInterface::class);

        $delegatedIdentity = $identityFactory->createDelegatedIdentity($originalIdentity, $delegationIdentifier);

        $this->assertNull($delegatedIdentity->profileImage());
        $this->assertSame(Language::KOREAN, $delegatedIdentity->language());
        $this->assertTrue($delegatedIdentity->isDelegatedIdentity());
    }
}
