<?php

declare(strict_types=1);

namespace Tests\Auth\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Auth\Infrastructure\Repository\UserRepository;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\CreateUser;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        $this->assertInstanceOf(UserRepository::class, $repository);
    }

    /**
     * 正常系: findByEmailでユーザーが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailReturnsUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $email = 'findbyemail@example.com';
        CreateUser::create($userIdentifier, ['email' => $email]);
        CreateUser::createServiceRole($userIdentifier, 'wiki', 'editor');
        CreateUser::createSocialConnection($userIdentifier, SocialProvider::GOOGLE, 'google-user-123');

        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findByEmail(new Email($email));

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame((string) $userIdentifier, (string) $result->userIdentifier());
        $this->assertSame($email, (string) $result->email());
        $this->assertCount(1, $result->serviceRoles());
        $this->assertSame('wiki', $result->serviceRoles()[0]->service());
        $this->assertSame('editor', $result->serviceRoles()[0]->role());
        $this->assertCount(1, $result->socialConnections());
        $this->assertSame(SocialProvider::GOOGLE, $result->socialConnections()[0]->provider());
        $this->assertSame('google-user-123', $result->socialConnections()[0]->providerUserId());
    }

    /**
     * 正常系: findByEmailでユーザーが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findByEmail(new Email('nonexistent@example.com'));

        $this->assertNull($result);
    }

    /**
     * 正常系: findBySocialConnectionでユーザーが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySocialConnectionReturnsUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $providerUserId = 'line-user-456';
        CreateUser::create($userIdentifier, ['email' => 'social@example.com']);
        CreateUser::createSocialConnection($userIdentifier, SocialProvider::LINE, $providerUserId);

        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findBySocialConnection(SocialProvider::LINE, $providerUserId);

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame((string) $userIdentifier, (string) $result->userIdentifier());
    }

    /**
     * 正常系: findBySocialConnectionでユーザーが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySocialConnectionReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findBySocialConnection(SocialProvider::INSTAGRAM, 'nonexistent-id');

        $this->assertNull($result);
    }

    /**
     * 正常系: saveで新規ユーザーを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveCreatesNewUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $email = new Email('newuser@example.com');
        $emailVerifiedAt = new DateTimeImmutable('2024-01-01 12:00:00');

        $user = new User(
            $userIdentifier,
            new UserName('new-user'),
            $email,
            Language::JAPANESE,
            new ImagePath('/images/profile.jpg'),
            HashedPassword::fromPlain(new PlainPassword('password123')),
            [new ServiceRole('wiki', 'admin')],
            $emailVerifiedAt,
            [new SocialConnection(SocialProvider::GOOGLE, 'google-new-user')]
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($user);

        $this->assertDatabaseHas('users', [
            'id' => (string) $userIdentifier,
            'username' => 'new-user',
            'email' => 'newuser@example.com',
            'language' => 'ja',
            'profile_image' => '/images/profile.jpg',
        ]);

        $this->assertDatabaseHas('user_service_roles', [
            'user_id' => (string) $userIdentifier,
            'service' => 'wiki',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('user_social_connections', [
            'user_id' => (string) $userIdentifier,
            'provider' => 'google',
            'provider_user_id' => 'google-new-user',
        ]);

        // toDomainEntityでemail_verified_atが正しく変換されることを確認
        $result = $repository->findByEmail($email);
        $this->assertNotNull($result);
        $this->assertNotNull($result->emailVerifiedAt());
        $this->assertSame(
            $emailVerifiedAt->format('Y-m-d H:i:s'),
            $result->emailVerifiedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * 正常系: saveで既存ユーザーを更新できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        CreateUser::create($userIdentifier, [
            'email' => 'original@example.com',
            'username' => 'original-user',
        ]);
        CreateUser::createServiceRole($userIdentifier, 'wiki', 'viewer');

        $updatedUser = new User(
            $userIdentifier,
            new UserName('updated-user'),
            new Email('updated@example.com'),
            Language::KOREAN,
            null,
            HashedPassword::fromPlain(new PlainPassword('newpassword')),
            [new ServiceRole('wiki', 'admin'), new ServiceRole('site', 'manager')],
            new DateTimeImmutable('2024-06-01 00:00:00'),
            []
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($updatedUser);

        $this->assertDatabaseHas('users', [
            'id' => (string) $userIdentifier,
            'username' => 'updated-user',
            'email' => 'updated@example.com',
            'language' => 'ko',
            'profile_image' => null,
        ]);

        $this->assertDatabaseMissing('user_service_roles', [
            'user_id' => (string) $userIdentifier,
            'service' => 'wiki',
            'role' => 'viewer',
        ]);

        $this->assertDatabaseHas('user_service_roles', [
            'user_id' => (string) $userIdentifier,
            'service' => 'wiki',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('user_service_roles', [
            'user_id' => (string) $userIdentifier,
            'service' => 'site',
            'role' => 'manager',
        ]);

        $rolesCount = DB::table('user_service_roles')
            ->where('user_id', (string) $userIdentifier)
            ->count();
        $this->assertSame(2, $rolesCount);
    }

    /**
     * 正常系: email_verified_atがnullのユーザーを正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindUserWithNullEmailVerifiedAt(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $email = new Email('unverified@example.com');

        $user = new User(
            $userIdentifier,
            new UserName('unverified-user'),
            $email,
            Language::ENGLISH,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            [],
            null,
            []
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($user);

        $result = $repository->findByEmail($email);

        $this->assertNotNull($result);
        $this->assertNull($result->emailVerifiedAt());
    }

    /**
     * 正常系: 複数のソーシャル接続を持つユーザーを正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindUserWithMultipleSocialConnections(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $email = new Email('multisocial@example.com');

        $user = new User(
            $userIdentifier,
            new UserName('multi-social-user'),
            $email,
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            [],
            null,
            [
                new SocialConnection(SocialProvider::GOOGLE, 'google-id-1'),
                new SocialConnection(SocialProvider::LINE, 'line-id-1'),
                new SocialConnection(SocialProvider::INSTAGRAM, 'instagram-id-1'),
            ]
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($user);

        $result = $repository->findByEmail($email);

        $this->assertNotNull($result);
        $this->assertCount(3, $result->socialConnections());
    }
}
