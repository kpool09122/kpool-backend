<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalGroupRepositoryTest extends TestCase
{
    private function createTestPrincipalGroup(
        ?string $principalGroupId = null,
        ?string $accountId = null,
        string $name = 'Test Group',
        AccountRole $role = AccountRole::OWNER,
        bool $isDefault = true,
    ): PrincipalGroup {
        $principalGroupId ??= StrTestHelper::generateUuid();
        $accountId ??= StrTestHelper::generateUuid();

        // FK制約のためAccountを事前に作成
        CreateAccount::create($accountId);

        return new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
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
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $this->assertInstanceOf(PrincipalGroupRepository::class, $repository);
    }

    /**
     * 正常系: 正しくPrincipalGroupを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $principalGroup = $this->createTestPrincipalGroup(
            principalGroupId: $principalGroupId,
            accountId: $accountId,
            name: 'Owners Group',
            role: AccountRole::OWNER,
            isDefault: true,
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $this->assertDatabaseHas('account_principal_groups', [
            'id' => $principalGroupId,
            'account_id' => $accountId,
            'name' => 'Owners Group',
            'role' => 'owner',
            'is_default' => true,
        ]);
    }

    /**
     * 正常系: メンバーを含むPrincipalGroupを正しく保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithMembers(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();

        // FK制約のためIdentityを事前に作成
        CreateIdentity::create(
            new IdentityIdentifier($identityId1),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        CreateIdentity::create(
            new IdentityIdentifier($identityId2),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        $principalGroup = $this->createTestPrincipalGroup(
            principalGroupId: $principalGroupId,
            accountId: $accountId,
        );
        $this->createPrincipal($principalId1, $identityId1, $accountId);
        $this->createPrincipal($principalId2, $identityId2, $accountId);
        $principalGroup->addMember(new PrincipalIdentifier($principalId1));
        $principalGroup->addMember(new PrincipalIdentifier($principalId2));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $this->assertDatabaseHas('account_principal_groups', [
            'id' => $principalGroupId,
        ]);

        $this->assertDatabaseHas('account_principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId1,
        ]);

        $this->assertDatabaseHas('account_principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId2,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $principalGroup = $this->createTestPrincipalGroup(
            principalGroupId: $principalGroupId,
            accountId: $accountId,
            name: 'Test Group',
            role: AccountRole::ADMIN,
            isDefault: false,
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $result = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));

        $this->assertNotNull($result);
        $this->assertSame($principalGroupId, (string) $result->principalGroupIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame('Test Group', $result->name());
        $this->assertSame(AccountRole::ADMIN, $result->role());
        $this->assertFalse($result->isDefault());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: メンバーを含むPrincipalGroupを正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMembers(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        $principalGroup = $this->createTestPrincipalGroup(
            principalGroupId: $principalGroupId,
            accountId: $accountId,
        );
        $this->createPrincipal($principalId, $identityId, $accountId);
        $principalGroup->addMember(new PrincipalIdentifier($principalId));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $result = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));

        $this->assertNotNull($result);
        $this->assertCount(1, $result->members());
        $this->assertTrue($result->hasMember(new PrincipalIdentifier($principalId)));
    }

    /**
     * 正常系: 指定したIDを持つPrincipalGroupが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findById(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくAccountIdに紐づくPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $principalGroup1 = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Owners',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $principalGroup2 = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Members',
            AccountRole::BASIC,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup1);
        $repository->save($principalGroup2);

        $result = $repository->findByAccountId(new AccountIdentifier($accountId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Owners', $names);
        $this->assertContains('Members', $names);
    }

    /**
     * 正常系: 指定したAccountIdを持つPrincipalGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくIdentityIdに紐づくPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityId(): void
    {
        $identityId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $principalGroup1 = $this->createTestPrincipalGroup(name: 'Group 1');
        $this->createPrincipal($principalId, $identityId, (string) $principalGroup1->accountIdentifier());
        $principalGroup1->addMember(new PrincipalIdentifier($principalId));

        $principalGroup2 = $this->createTestPrincipalGroup(name: 'Group 2');
        $principalGroup2->addMember(new PrincipalIdentifier($principalId));

        // このグループにはメンバーを追加しない
        $principalGroup3 = $this->createTestPrincipalGroup(name: 'Group 3');

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup1);
        $repository->save($principalGroup2);
        $repository->save($principalGroup3);

        $result = $repository->findByPrincipalId(new PrincipalIdentifier($principalId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Group 1', $names);
        $this->assertContains('Group 2', $names);
        $this->assertNotContains('Group 3', $names);
    }

    /**
     * 正常系: 指定したIdentityIdを持つPrincipalGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByPrincipalId(new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくAccountIdとIdentityIdに紐づくPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndIdentityId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        $this->createPrincipal($principalId, $identityId, $accountId);

        $principalGroup1 = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Owners',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );
        $principalGroup1->addMember(new PrincipalIdentifier($principalId));

        $principalGroup2 = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Admins',
            AccountRole::ADMIN,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup2->addMember(new PrincipalIdentifier($principalId));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup1);
        $repository->save($principalGroup2);

        $result = $repository->findByAccountIdAndPrincipal(
            new AccountIdentifier($accountId),
            new PrincipalIdentifier($principalId)
        );

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Owners', $names);
        $this->assertContains('Admins', $names);
    }

    /**
     * 正常系: 指定したAccountIdとIdentityIdに紐づくPrincipalGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndIdentityIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByAccountIdAndPrincipal(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertSame([], $result);
    }

    /**
     * 正常系: IdentityIdが存在するが別のAccountの場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndIdentityIdWhenDifferentAccount(): void
    {
        $accountId1 = StrTestHelper::generateUuid();
        $accountId2 = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId1);
        CreateAccount::create($accountId2);
        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        $this->createPrincipal($principalId, $identityId, $accountId1);

        // accountId1にidentityIdを追加
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId1),
            'Test Group',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        // accountId2で検索すると見つからない
        $result = $repository->findByAccountIdAndPrincipal(
            new AccountIdentifier($accountId2),
            new PrincipalIdentifier($principalId)
        );

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくAccountIdに紐づくデフォルトPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $defaultGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Default Group',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $nonDefaultGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Non Default Group',
            AccountRole::BASIC,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($defaultGroup);
        $repository->save($nonDefaultGroup);

        $result = $repository->findDefaultByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame('Default Group', $result->name());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: 指定したAccountIdを持つデフォルトPrincipalGroupが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findDefaultByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくPrincipalGroupを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $principalGroup = $this->createTestPrincipalGroup(principalGroupId: $principalGroupId);
        $this->createPrincipal($principalId, $identityId, (string) $principalGroup->accountIdentifier());
        $principalGroup->addMember(new PrincipalIdentifier($principalId));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        // 削除前に存在確認
        $this->assertNotNull($repository->findById(new PrincipalGroupIdentifier($principalGroupId)));

        // 削除
        $repository->delete($principalGroup);

        // 削除後の確認
        $this->assertNull($repository->findById(new PrincipalGroupIdentifier($principalGroupId)));
        $this->assertDatabaseMissing('account_principal_groups', ['id' => $principalGroupId]);
        $this->assertDatabaseMissing('account_principal_group_memberships', ['principal_group_id' => $principalGroupId]);
    }

    /**
     * 正常系: AccountIdとRoleでPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndRole(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $ownerGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Owners',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $memberGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Members',
            AccountRole::BASIC,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($ownerGroup);
        $repository->save($memberGroup);

        $result = $repository->findByAccountIdAndRole(
            new AccountIdentifier($accountId),
            AccountRole::BASIC
        );

        $this->assertNotNull($result);
        $this->assertSame('Members', $result->name());
        $this->assertSame(AccountRole::BASIC, $result->role());
    }

    /**
     * 正常系: AccountIdとRoleに一致するPrincipalGroupが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndRoleWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByAccountIdAndRole(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AccountRole::BASIC
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 異なるRoleでは取得できないこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndRoleWithDifferentRole(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);

        $ownerGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId),
            'Owners',
            AccountRole::OWNER,
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($ownerGroup);

        // MEMBERで検索するとOWNERグループは見つからない
        $result = $repository->findByAccountIdAndRole(
            new AccountIdentifier($accountId),
            AccountRole::BASIC
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 異なるAccountでは取得できないこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdAndRoleWithDifferentAccount(): void
    {
        $accountId1 = StrTestHelper::generateUuid();
        $accountId2 = StrTestHelper::generateUuid();
        CreateAccount::create($accountId1);
        CreateAccount::create($accountId2);

        $memberGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier($accountId1),
            'Members',
            AccountRole::BASIC,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($memberGroup);

        // 別のAccountIdで検索すると見つからない
        $result = $repository->findByAccountIdAndRole(
            new AccountIdentifier($accountId2),
            AccountRole::BASIC
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 既存のPrincipalGroupを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingGroup(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();

        CreateIdentity::create(
            new IdentityIdentifier($identityId1),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        CreateIdentity::create(
            new IdentityIdentifier($identityId2),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );
        // 最初のメンバーで保存
        $principalGroup = $this->createTestPrincipalGroup(
            principalGroupId: $principalGroupId,
            accountId: $accountId,
        );
        $this->createPrincipal($principalId1, $identityId1, $accountId);
        $this->createPrincipal($principalId2, $identityId2, $accountId);
        $principalGroup->addMember(new PrincipalIdentifier($principalId1));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        // 取得してメンバーを変更
        $retrieved = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));
        $this->assertNotNull($retrieved);
        $this->assertCount(1, $retrieved->members());

        // 新しいPrincipalGroupエンティティを作成して異なるメンバーで保存
        $updatedGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Updated Group',
            AccountRole::ADMIN,
            false,
            new DateTimeImmutable(),
        );
        $updatedGroup->addMember(new PrincipalIdentifier($principalId2));

        $repository->save($updatedGroup);

        // 更新後の確認
        $result = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));
        $this->assertNotNull($result);
        $this->assertSame('Updated Group', $result->name());
        $this->assertSame(AccountRole::ADMIN, $result->role());
        $this->assertFalse($result->isDefault());
        $this->assertCount(1, $result->members());
        $this->assertTrue($result->hasMember(new PrincipalIdentifier($principalId2)));
        $this->assertFalse($result->hasMember(new PrincipalIdentifier($principalId1)));
    }

    private function createPrincipal(string $principalId, string $identityId, string $accountId): void
    {
        DB::table('account_principals')->insert([
            'id' => $principalId,
            'identity_id' => $identityId,
            'account_id' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
