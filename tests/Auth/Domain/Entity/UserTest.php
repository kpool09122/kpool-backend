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
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Source\Shared\Domain\ValueObject\Email;
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
        $email = new Email('user@example.com');
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $serviceRoles = [new ServiceRole('auth', 'admin')];
        $verifiedAt = new DateTimeImmutable();

        $user = new User($userIdentifier, $email, $hashedPassword, $serviceRoles, $verifiedAt);

        $this->assertSame($userIdentifier, $user->userIdentifier());
        $this->assertSame($email, $user->email());
        $this->assertSame($hashedPassword, $user->hashedPassword());
        $this->assertSame($verifiedAt, $user->emailVerifiedAt());
        $this->assertSame($serviceRoles, $user->serviceRoles());
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
     * @param UserIdentifier|null $userIdentifier
     * @param Email|null $email
     * @param HashedPassword|null $hashedPassword
     * @param ServiceRole[] $serviceRoles
     * @param DateTimeImmutable|null $verifiedAt
     * @return User
     */
    private function createUser(
        ?UserIdentifier $userIdentifier = null,
        ?Email $email = null,
        ?HashedPassword $hashedPassword = null,
        array $serviceRoles = [],
        ?DateTimeImmutable $verifiedAt = null,
    ): User {
        return new User(
            $userIdentifier ?? new UserIdentifier(StrTestHelper::generateUlid()),
            $email ?? new Email('user@example.com'),
            $hashedPassword ?? HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            $serviceRoles ?: [new ServiceRole('auth', 'user')],
            $verifiedAt
        );
    }
}
