<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\IdentityGroup\Infrastructure\Repository\IdentityGroupRepository;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityGroupRepositoryTest extends TestCase
{
    private function createTestIdentityGroup(
        ?string $identityGroupId = null,
        ?string $accountId = null,
        string $name = 'Test Group',
        AccountRole $role = AccountRole::OWNER,
        bool $isDefault = true,
    ): IdentityGroup {
        $identityGroupId ??= StrTestHelper::generateUuid();
        $accountId ??= StrTestHelper::generateUuid();

        // FK制約のためAccountを事前に作成
        CreateAccount::create($accountId);

        return new IdentityGroup(
            new IdentityGroupIdentifier($identityGroupId),
            new AccountIdentifier($accountId),
            $name,
            $role,
            $isDefault,
            new DateTimeImmutable(),
        );
    }

    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $this->assertInstanceOf(IdentityGroupRepository::class, $repository);
    }

    /**
     * 正常系: 正しくIdentityGroupを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $identityGroup = $this->createTestIdentityGroup(
            identityGroupId: $identityGroupId,
            accountId: $accountId,
            name: 'Owners Group',
            role: AccountRole::OWNER,
            isDefault: true,
        );

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        $this->assertDatabaseHas('identity_groups', [
            'id' => $identityGroupId,
            'account_id' => $accountId,
            'name' => 'Owners Group',
            'role' => 'owner',
            'is_default' => true,
        ]);
    }

    /**
     * 正常系: メンバーを含むIdentityGroupを正しく保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithMembers(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        // FK制約のためIdentityを事前に作成
        CreateIdentity::create(
            new IdentityIdentifier($identityId1),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        CreateIdentity::create(
            new IdentityIdentifier($identityId2),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $identityGroup = $this->createTestIdentityGroup(
            identityGroupId: $identityGroupId,
            accountId: $accountId,
        );
        $identityGroup->addMember(new IdentityIdentifier($identityId1));
        $identityGroup->addMember(new IdentityIdentifier($identityId2));

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        $this->assertDatabaseHas('identity_groups', [
            'id' => $identityGroupId,
        ]);

        $this->assertDatabaseHas('identity_group_memberships', [
            'identity_group_id' => $identityGroupId,
            'identity_id' => $identityId1,
        ]);

        $this->assertDatabaseHas('identity_group_memberships', [
            'identity_group_id' => $identityGroupId,
            'identity_id' => $identityId2,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくIdentityGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $identityGroup = $this->createTestIdentityGroup(
            identityGroupId: $identityGroupId,
            accountId: $accountId,
            name: 'Test Group',
            role: AccountRole::ADMIN,
            isDefault: false,
        );

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        $result = $repository->findById(new IdentityGroupIdentifier($identityGroupId));

        $this->assertNotNull($result);
        $this->assertSame($identityGroupId, (string) $result->identityGroupIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame('Test Group', $result->name());
        $this->assertSame(AccountRole::ADMIN, $result->role());
        $this->assertFalse($result->isDefault());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: メンバーを含むIdentityGroupを正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMembers(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $identityGroup = $this->createTestIdentityGroup(
            identityGroupId: $identityGroupId,
            accountId: $accountId,
        );
        $identityGroup->addMember(new IdentityIdentifier($identityId));

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        $result = $repository->findById(new IdentityGroupIdentifier($identityGroupId));

        $this->assertNotNull($result);
        $this->assertCount(1, $result->members());
        $this->assertTrue($result->hasMember(new IdentityIdentifier($identityId)));
    }

    /**
     * 正常系: 指定したIDを持つIdentityGroupが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $result = $repository->findById(new IdentityGroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくAccountIdに紐づくIdentityGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $identityGroup1 = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Owners',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $identityGroup2 = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Members',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup1);
        $repository->save($identityGroup2);

        $result = $repository->findByAccountId(new AccountIdentifier($accountId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Owners', $names);
        $this->assertContains('Members', $names);
    }

    /**
     * 正常系: 指定したAccountIdを持つIdentityGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくIdentityIdに紐づくIdentityGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityId(): void
    {
        $identityId = StrTestHelper::generateUuid();
        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $identityGroup1 = $this->createTestIdentityGroup(name: 'Group 1');
        $identityGroup1->addMember(new IdentityIdentifier($identityId));

        $identityGroup2 = $this->createTestIdentityGroup(name: 'Group 2');
        $identityGroup2->addMember(new IdentityIdentifier($identityId));

        // このグループにはメンバーを追加しない
        $identityGroup3 = $this->createTestIdentityGroup(name: 'Group 3');

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup1);
        $repository->save($identityGroup2);
        $repository->save($identityGroup3);

        $result = $repository->findByIdentityId(new IdentityIdentifier($identityId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Group 1', $names);
        $this->assertContains('Group 2', $names);
        $this->assertNotContains('Group 3', $names);
    }

    /**
     * 正常系: 指定したIdentityIdを持つIdentityGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $result = $repository->findByIdentityId(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくAccountIdに紐づくデフォルトIdentityGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $defaultGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Default Group',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $nonDefaultGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Non Default Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($defaultGroup);
        $repository->save($nonDefaultGroup);

        $result = $repository->findDefaultByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame('Default Group', $result->name());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: 指定したAccountIdを持つデフォルトIdentityGroupが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $result = $repository->findDefaultByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくIdentityGroupを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $identityGroup = $this->createTestIdentityGroup(identityGroupId: $identityGroupId);
        $identityGroup->addMember(new IdentityIdentifier($identityId));

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        // 削除前に存在確認
        $this->assertNotNull($repository->findById(new IdentityGroupIdentifier($identityGroupId)));

        // 削除
        $repository->delete($identityGroup);

        // 削除後の確認
        $this->assertNull($repository->findById(new IdentityGroupIdentifier($identityGroupId)));
        $this->assertDatabaseMissing('identity_groups', ['id' => $identityGroupId]);
        $this->assertDatabaseMissing('identity_group_memberships', ['identity_group_id' => $identityGroupId]);
    }

    /**
     * 正常系: 既存のIdentityGroupを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingGroup(): void
    {
        $identityGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId1),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        CreateIdentity::create(
            new IdentityIdentifier($identityId2),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        // 最初のメンバーで保存
        $identityGroup = $this->createTestIdentityGroup(
            identityGroupId: $identityGroupId,
            accountId: $accountId,
        );
        $identityGroup->addMember(new IdentityIdentifier($identityId1));

        $repository = $this->app->make(IdentityGroupRepositoryInterface::class);
        $repository->save($identityGroup);

        // 取得してメンバーを変更
        $retrieved = $repository->findById(new IdentityGroupIdentifier($identityGroupId));
        $this->assertNotNull($retrieved);
        $this->assertCount(1, $retrieved->members());

        // 新しいIdentityGroupエンティティを作成して異なるメンバーで保存
        $updatedGroup = new IdentityGroup(
            new IdentityGroupIdentifier($identityGroupId),
            new AccountIdentifier($accountId),
            'Updated Group',
            AccountRole::ADMIN,
            false,
            new DateTimeImmutable(),
        );
        $updatedGroup->addMember(new IdentityIdentifier($identityId2));

        $repository->save($updatedGroup);

        // 更新後の確認
        $result = $repository->findById(new IdentityGroupIdentifier($identityGroupId));
        $this->assertNotNull($result);
        $this->assertSame('Updated Group', $result->name());
        $this->assertSame(AccountRole::ADMIN, $result->role());
        $this->assertFalse($result->isDefault());
        $this->assertCount(1, $result->members());
        $this->assertTrue($result->hasMember(new IdentityIdentifier($identityId2)));
        $this->assertFalse($result->hasMember(new IdentityIdentifier($identityId1)));
    }
}
