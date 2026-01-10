<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Infrastructure\Repository\RoleRepository;
use Tests\Helper\CreatePolicy;
use Tests\Helper\CreateRole;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $this->assertInstanceOf(RoleRepository::class, $repository);
    }

    /**
     * 正常系: 正しくRoleを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $roleId = StrTestHelper::generateUuid();

        $role = new Role(
            new RoleIdentifier($roleId),
            'Administrator',
            [],
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(RoleRepositoryInterface::class);
        $repository->save($role);

        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Administrator',
            'is_system_role' => true,
        ]);
    }

    /**
     * 正常系: RoleにPolicyをアタッチして保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithPolicies(): void
    {
        $roleId = StrTestHelper::generateUuid();
        $policyId1 = StrTestHelper::generateUuid();
        $policyId2 = StrTestHelper::generateUuid();

        // Policyを先に作成
        CreatePolicy::create(new PolicyIdentifier($policyId1));
        CreatePolicy::create(new PolicyIdentifier($policyId2));

        $role = new Role(
            new RoleIdentifier($roleId),
            'Multi Policy Role',
            [
                new PolicyIdentifier($policyId1),
                new PolicyIdentifier($policyId2),
            ],
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(RoleRepositoryInterface::class);
        $repository->save($role);

        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Multi Policy Role',
        ]);
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId1,
        ]);
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId2,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくRoleを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $roleId = StrTestHelper::generateUuid();

        CreateRole::create(
            new RoleIdentifier($roleId),
            [
                'name' => 'Test Role',
                'is_system_role' => true,
            ]
        );

        $repository = $this->app->make(RoleRepositoryInterface::class);
        $result = $repository->findById(new RoleIdentifier($roleId));

        $this->assertNotNull($result);
        $this->assertSame($roleId, (string) $result->roleIdentifier());
        $this->assertSame('Test Role', $result->name());
        $this->assertTrue($result->isSystemRole());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: アタッチされたPolicyも取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithPolicies(): void
    {
        $roleId = StrTestHelper::generateUuid();
        $policyId1 = StrTestHelper::generateUuid();
        $policyId2 = StrTestHelper::generateUuid();

        CreatePolicy::create(new PolicyIdentifier($policyId1));
        CreatePolicy::create(new PolicyIdentifier($policyId2));

        CreateRole::create(
            new RoleIdentifier($roleId),
            [
                'name' => 'Test Role',
                'policies' => [$policyId1, $policyId2],
            ]
        );

        $repository = $this->app->make(RoleRepositoryInterface::class);
        $result = $repository->findById(new RoleIdentifier($roleId));

        $this->assertNotNull($result);
        $this->assertCount(2, $result->policies());
        $policyIds = array_map(fn ($p) => (string) $p, $result->policies());
        $this->assertContains($policyId1, $policyIds);
        $this->assertContains($policyId2, $policyIds);
    }

    /**
     * 正常系: 指定したIDを持つRoleが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $result = $repository->findById(new RoleIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 全てのRoleを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAll(): void
    {
        $roleId1 = StrTestHelper::generateUuid();
        $roleId2 = StrTestHelper::generateUuid();

        CreateRole::create(
            new RoleIdentifier($roleId1),
            ['name' => 'Role 1']
        );
        CreateRole::create(
            new RoleIdentifier($roleId2),
            ['name' => 'Role 2']
        );

        $repository = $this->app->make(RoleRepositoryInterface::class);
        $result = $repository->findAll();

        $this->assertCount(2, $result);
        $names = array_map(static fn ($r) => $r->name(), $result);
        $this->assertContains('Role 1', $names);
        $this->assertContains('Role 2', $names);
    }

    /**
     * 正常系: Roleが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAllWhenEmpty(): void
    {
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $result = $repository->findAll();

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくRoleを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $roleId = StrTestHelper::generateUuid();

        CreateRole::create(new RoleIdentifier($roleId));

        // 削除前に存在確認
        $this->assertDatabaseHas('roles', ['id' => $roleId]);

        $repository = $this->app->make(RoleRepositoryInterface::class);

        $role = new Role(
            new RoleIdentifier($roleId),
            'Test Role',
            [],
            false,
            new DateTimeImmutable(),
        );

        $repository->delete($role);

        // 削除後の確認
        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    /**
     * 正常系: 削除時にアタッチメントも削除されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteWithPolicies(): void
    {
        $roleId = StrTestHelper::generateUuid();
        $policyId = StrTestHelper::generateUuid();

        CreatePolicy::create(new PolicyIdentifier($policyId));
        CreateRole::create(
            new RoleIdentifier($roleId),
            ['policies' => [$policyId]]
        );

        // 削除前に存在確認
        $this->assertDatabaseHas('roles', ['id' => $roleId]);
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId,
        ]);

        $repository = $this->app->make(RoleRepositoryInterface::class);

        $role = new Role(
            new RoleIdentifier($roleId),
            'Test Role',
            [new PolicyIdentifier($policyId)],
            false,
            new DateTimeImmutable(),
        );

        $repository->delete($role);

        // 削除後の確認
        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
        $this->assertDatabaseMissing('role_policy_attachments', [
            'role_id' => $roleId,
        ]);
    }

    /**
     * 正常系: 既存のRoleを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingRole(): void
    {
        $roleId = StrTestHelper::generateUuid();

        CreateRole::create(
            new RoleIdentifier($roleId),
            [
                'name' => 'Original Name',
                'is_system_role' => false,
            ]
        );

        // 更新前の確認
        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Original Name',
            'is_system_role' => false,
        ]);

        $repository = $this->app->make(RoleRepositoryInterface::class);

        $updatedRole = new Role(
            new RoleIdentifier($roleId),
            'Updated Name',
            [],
            true,
            new DateTimeImmutable(),
        );

        $repository->save($updatedRole);

        // 更新後の確認
        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Updated Name',
            'is_system_role' => true,
        ]);
    }

    /**
     * 正常系: Policyの追加・削除が正しくsyncされること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveSyncsPolicies(): void
    {
        $roleId = StrTestHelper::generateUuid();
        $policyId1 = StrTestHelper::generateUuid();
        $policyId2 = StrTestHelper::generateUuid();
        $policyId3 = StrTestHelper::generateUuid();

        // Policyを先に作成
        CreatePolicy::create(new PolicyIdentifier($policyId1));
        CreatePolicy::create(new PolicyIdentifier($policyId2));
        CreatePolicy::create(new PolicyIdentifier($policyId3));

        // 初期状態: policy1, policy2 をアタッチ
        CreateRole::create(
            new RoleIdentifier($roleId),
            ['policies' => [$policyId1, $policyId2]]
        );

        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId1,
        ]);
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId2,
        ]);

        // 更新: policy1 を削除、policy3 を追加 (policy2 は維持)
        $repository = $this->app->make(RoleRepositoryInterface::class);

        $updatedRole = new Role(
            new RoleIdentifier($roleId),
            'Test Role',
            [
                new PolicyIdentifier($policyId2),
                new PolicyIdentifier($policyId3),
            ],
            false,
            new DateTimeImmutable(),
        );

        $repository->save($updatedRole);

        // policy1 は削除されている
        $this->assertDatabaseMissing('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId1,
        ]);
        // policy2, policy3 は存在する
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId2,
        ]);
        $this->assertDatabaseHas('role_policy_attachments', [
            'role_id' => $roleId,
            'policy_id' => $policyId3,
        ]);
    }
}
