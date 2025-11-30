<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class UserTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $profileImage = new ImagePath('/resources/path/test.png');
        $language = Language::JAPANESE;
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $serviceRoles = [new ServiceRole('auth', 'admin')];
        $verifiedAt = new DateTimeImmutable();
        $socialConnection = new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id');
        $connections = [$socialConnection];

        $user = new User($userIdentifier, $userName, $email, $language, $profileImage, $hashedPassword, $serviceRoles, $verifiedAt, $connections);

        $this->assertSame($userIdentifier, $user->userIdentifier());
        $this->assertSame($userName, $user->userName());
        $this->assertSame($email, $user->email());
        $this->assertSame($language, $user->language());
        $this->assertSame($profileImage, $user->profileImage());
        $this->assertSame($hashedPassword, $user->hashedPassword());
        $this->assertSame($verifiedAt, $user->emailVerifiedAt());
        $this->assertSame($serviceRoles, $user->serviceRoles());
        $this->assertSame($connections, $user->socialConnections());
    }

    /**
     * 異常系: メールアドレスが認証されていない時、例外がスローされること.
     *
     * @return void
     */
    public function testIsEmailVerifiedThrowsWhenNotVerified(): void
    {
        $user = $this->createUser(verifiedAt: null);

        $this->expectException(DomainException::class);

        $user->isEmailVerified();
    }

    /**
     * 正常系: 入力されたPasswordのハッシュ値が一致しない場合、例外がスローされること.
     *
     * @return void
     */
    public function testVerifyPasswordThrowsWhenPasswordDoesNotMatch(): void
    {
        $user = $this->createUser();

        $this->expectException(DomainException::class);

        $user->verifyPassword(new PlainPassword('WrongPass1!'));
    }

    /**
     * 正常系: 引数に渡されたサービスに紐づくロールのみ取得できること.
     *
     * @return void
     */
    public function testRolesForServiceFiltersByService(): void
    {
        $roles = [
            new ServiceRole('auth', 'admin'),
            new ServiceRole('auth', 'viewer'),
            new ServiceRole('billing', 'member'),
        ];
        $user = $this->createUser(serviceRoles: $roles);

        $authRoles = $user->rolesForService('auth');

        $this->assertCount(2, $authRoles);
        $this->assertContainsOnlyInstancesOf(ServiceRole::class, $authRoles);
        foreach ($authRoles as $role) {
            $this->assertSame('auth', $role->service());
        }
    }

    /**
     * 正常系: 引数に渡されたサービスロールを持っているかどうかを判定できること.
     *
     * @return void
     */
    public function testHasRoleReturnsTrueWhenRoleExists(): void
    {
        $roles = [
            new ServiceRole('auth', 'admin'),
            new ServiceRole('billing', 'member'),
        ];
        $user = $this->createUser(serviceRoles: $roles);

        $this->assertTrue($user->hasRole(new ServiceRole('billing', 'member')));
        $this->assertFalse($user->hasRole(new ServiceRole('billing', 'admin')));
    }

    /**
     * 正常系: ソーシャルコネクションを正しく追加できること.
     *
     * @return void
     */
    public function testAddSocialConnection(): void
    {
        $connection = new SocialConnection(SocialProvider::INSTAGRAM, 'provider-user-id');
        $user = $this->createUser();
        $user->addSocialConnection($connection);
        $this->assertContains($connection, $user->socialConnections());
    }

    /**
     * 異常系: 重複するソーシャルコネクションを追加しようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testConnectionWhenAddingDuplicateSocialConnection(): void
    {
        $user = $this->createUser();
        $this->expectException(DomainException::class);
        $user->addSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id'));
    }

    /**
     * @param UserIdentifier|null $userIdentifier
     * @param UserName|null $userName
     * @param Email|null $email
     * @param Language|null $language
     * @param ImagePath|null $profileImage
     * @param HashedPassword|null $hashedPassword
     * @param ServiceRole[] $serviceRoles
     * @param DateTimeImmutable|null $verifiedAt
     * @param SocialConnection[] $connections
     * @return User
     */
    private function createUser(
        ?UserIdentifier    $userIdentifier = null,
        ?UserName          $userName = null,
        ?Email             $email = null,
        ?Language          $language = null,
        ?ImagePath         $profileImage = null,
        ?HashedPassword    $hashedPassword = null,
        array              $serviceRoles = [],
        ?DateTimeImmutable $verifiedAt = null,
        array              $connections = []
    ): User {
        return new User(
            $userIdentifier ?? new UserIdentifier(StrTestHelper::generateUlid()),
            $userName ?? new UserName('test-user'),
            $email ?? new Email('user@example.com'),
            $language ?? Language::JAPANESE,
            $profileImage ?? new ImagePath('/resources/path/test.png'),
            $hashedPassword ?? HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            $serviceRoles ?: [new ServiceRole('auth', 'user')],
            $verifiedAt,
            $connections ?: [new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id')]
        );
    }
}
