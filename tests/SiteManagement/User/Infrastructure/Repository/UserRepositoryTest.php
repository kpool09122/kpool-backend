<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Source\SiteManagement\User\Infrastructure\Repository\UserRepository;
use Tests\Helper\CreateIdentity;
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
     * 正常系: findByIdでUserが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        CreateUser::create($userIdentifier, $identityIdentifier, ['role' => Role::ADMIN]);

        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findById($userIdentifier);

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame((string) $userIdentifier, (string) $result->userIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::ADMIN, $result->role());
    }

    /**
     * 正常系: findByIdでUserが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findById(new UserIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: findByIdentityIdentifierでUserが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierReturnsUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        CreateUser::create($userIdentifier, $identityIdentifier, ['role' => Role::NONE]);

        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifier($identityIdentifier);

        $this->assertNotNull($result);
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame((string) $userIdentifier, (string) $result->userIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame(Role::NONE, $result->role());
    }

    /**
     * 正常系: findByIdentityIdentifierでUserが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifier(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: saveで新規Userを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveCreatesNewUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $user = new User(
            $userIdentifier,
            $identityIdentifier,
            Role::ADMIN,
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($user);

        $this->assertDatabaseHas('site_management_users', [
            'id' => (string) $userIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'role' => 'admin',
        ]);
    }

    /**
     * 正常系: saveで既存Userを更新できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingUser(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        CreateUser::create($userIdentifier, $identityIdentifier, ['role' => Role::NONE]);

        $updatedUser = new User(
            $userIdentifier,
            $identityIdentifier,
            Role::ADMIN,
        );

        $repository = $this->app->make(UserRepositoryInterface::class);
        $repository->save($updatedUser);

        $this->assertDatabaseHas('site_management_users', [
            'id' => (string) $userIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'role' => 'admin',
        ]);
    }

    /**
     * 正常系: 各Roleを正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithDifferentRoles(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);

        foreach (Role::cases() as $role) {
            $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
            $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identityIdentifier, ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']);

            $user = new User(
                $userIdentifier,
                $identityIdentifier,
                $role,
            );

            $repository->save($user);
            $result = $repository->findById($userIdentifier);

            $this->assertNotNull($result);
            $this->assertSame($role, $result->role());
        }
    }
}
