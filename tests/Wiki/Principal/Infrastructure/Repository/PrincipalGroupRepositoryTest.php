<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalGroupRepositoryTest extends TestCase
{
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

        CreateAccount::create($accountId);

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Default Group',
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $this->assertDatabaseHas('principal_groups', [
            'id' => $principalGroupId,
            'account_id' => $accountId,
            'name' => 'Default Group',
            'is_default' => true,
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

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            [
                'name' => 'Test Group',
                'is_default' => false,
            ]
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));

        $this->assertNotNull($result);
        $this->assertSame($principalGroupId, (string) $result->principalGroupIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame('Test Group', $result->name());
        $this->assertFalse($result->isDefault());
        $this->assertNotNull($result->createdAt());
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
        $principalGroupId1 = StrTestHelper::generateUuid();
        $principalGroupId2 = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId1),
            new AccountIdentifier($accountId),
            [
                'name' => 'Default Group',
                'is_default' => true,
            ]
        );
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId2),
            new AccountIdentifier($accountId),
            [
                'name' => 'Other Group',
                'is_default' => false,
            ]
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByAccountId(new AccountIdentifier($accountId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Default Group', $names);
        $this->assertContains('Other Group', $names);
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
     * 正常系: 正しくAccountIdに紐づくデフォルトPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $defaultGroupId = StrTestHelper::generateUuid();
        $nonDefaultGroupId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($defaultGroupId),
            new AccountIdentifier($accountId),
            [
                'name' => 'Default Group',
                'is_default' => true,
            ]
        );
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($nonDefaultGroupId),
            new AccountIdentifier($accountId),
            [
                'name' => 'Non Default Group',
                'is_default' => false,
            ]
        );

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
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
        $accountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
        );

        // 削除前に存在確認
        $this->assertDatabaseHas('principal_groups', ['id' => $principalGroupId]);

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);

        // 削除対象のエンティティを直接作成
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $repository->delete($principalGroup);

        // 削除後の確認
        $this->assertDatabaseMissing('principal_groups', ['id' => $principalGroupId]);
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

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            [
                'name' => 'Original Name',
                'is_default' => true,
            ]
        );

        // 更新前の確認
        $this->assertDatabaseHas('principal_groups', [
            'id' => $principalGroupId,
            'name' => 'Original Name',
            'is_default' => true,
        ]);

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);

        // 新しいPrincipalGroupエンティティを作成して更新
        $updatedGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Updated Name',
            false,
            new DateTimeImmutable(),
        );

        $repository->save($updatedGroup);

        // 更新後の確認
        $this->assertDatabaseHas('principal_groups', [
            'id' => $principalGroupId,
            'name' => 'Updated Name',
            'is_default' => false,
        ]);
    }

    /**
     * 正常系: PrincipalIdに紐づくPrincipalGroupを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $principalGroupId1 = StrTestHelper::generateUuid();
        $principalGroupId2 = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId1),
            new AccountIdentifier($accountId),
            ['name' => 'Group 1']
        );
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId2),
            new AccountIdentifier($accountId),
            ['name' => 'Group 2']
        );
        CreateIdentity::create(new IdentityIdentifier($identityId), ['email' => 'test1@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 中間テーブルにメンバーシップを追加
        CreatePrincipalGroupMembership::create($principalGroupId1, $principalId);
        CreatePrincipalGroupMembership::create($principalGroupId2, $principalId);

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByPrincipalId(new PrincipalIdentifier($principalId));

        $this->assertCount(2, $result);
        $names = array_map(static fn ($g) => $g->name(), $result);
        $this->assertContains('Group 1', $names);
        $this->assertContains('Group 2', $names);
    }

    /**
     * 正常系: 指定したPrincipalIdを持つPrincipalGroupが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findByPrincipalId(new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: PrincipalGroupのメンバーが正しく保存されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveSyncsMembersAddition(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateIdentity::create(new IdentityIdentifier($identityId1), ['email' => 'test1@example.com']);
        CreateIdentity::create(new IdentityIdentifier($identityId2), ['email' => 'test2@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId1),
            new IdentityIdentifier($identityId1),
        );
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId2),
            new IdentityIdentifier($identityId2),
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId1));
        $principalGroup->addMember(new PrincipalIdentifier($principalId2));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $this->assertDatabaseHas('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId1,
        ]);
        $this->assertDatabaseHas('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId2,
        ]);
    }

    /**
     * 正常系: PrincipalGroupのメンバーが正しく削除されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveSyncsMembersRemoval(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateIdentity::create(new IdentityIdentifier($identityId1), ['email' => 'test1@example.com']);
        CreateIdentity::create(new IdentityIdentifier($identityId2), ['email' => 'test2@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId1),
            new IdentityIdentifier($identityId1),
        );
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId2),
            new IdentityIdentifier($identityId2),
        );
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
        );
        CreatePrincipalGroupMembership::create($principalGroupId, $principalId1);
        CreatePrincipalGroupMembership::create($principalGroupId, $principalId2);

        // 事前確認
        $this->assertDatabaseHas('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId1,
        ]);
        $this->assertDatabaseHas('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId2,
        ]);

        // principalId2 を削除
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember(new PrincipalIdentifier($principalId1));

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($principalGroup);

        $this->assertDatabaseHas('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId1,
        ]);
        $this->assertDatabaseMissing('principal_group_memberships', [
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId2,
        ]);
    }

    /**
     * 正常系: findByIdでメンバーも取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMembers(): void
    {
        $principalGroupId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateIdentity::create(new IdentityIdentifier($identityId1), ['email' => 'test1@example.com']);
        CreateIdentity::create(new IdentityIdentifier($identityId2), ['email' => 'test2@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId1),
            new IdentityIdentifier($identityId1),
        );
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId2),
            new IdentityIdentifier($identityId2),
        );
        CreatePrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($accountId),
        );
        CreatePrincipalGroupMembership::create($principalGroupId, $principalId1);
        CreatePrincipalGroupMembership::create($principalGroupId, $principalId2);

        $repository = $this->app->make(PrincipalGroupRepositoryInterface::class);
        $result = $repository->findById(new PrincipalGroupIdentifier($principalGroupId));

        $this->assertNotNull($result);
        $this->assertSame(2, $result->memberCount());
        $this->assertTrue($result->hasMember(new PrincipalIdentifier($principalId1)));
        $this->assertTrue($result->hasMember(new PrincipalIdentifier($principalId2)));
    }
}
