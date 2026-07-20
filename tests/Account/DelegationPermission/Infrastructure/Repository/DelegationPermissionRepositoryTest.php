<?php

declare(strict_types=1);

namespace Tests\Account\DelegationPermission\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\DelegationPermission\Infrastructure\Repository\DelegationPermissionRepository;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountPrincipalGroup;
use Tests\Helper\CreateDelegationPermission;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationPermissionRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $this->assertInstanceOf(DelegationPermissionRepository::class, $repository);
    }

    /**
     * 正常系: 正しくDelegationPermissionを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId = StrTestHelper::generateUuid();
        $sourceAccountId = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($sourceAccountId),
        );

        $delegationPermission = new DelegationPermission(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $repository->save($delegationPermission);

        $this->assertDatabaseHas('delegation_permissions', [
            'id' => $delegationPermissionId,
            'principal_group_id' => $principalGroupId,
            'target_account_id' => $targetAccountId,
            'affiliation_id' => $affiliationId,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくDelegationPermissionを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId = StrTestHelper::generateUuid();
        $sourceAccountId = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($sourceAccountId),
        );
        CreateDelegationPermission::create(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
        );

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->findById(new DelegationPermissionIdentifier($delegationPermissionId));

        $this->assertNotNull($result);
        $this->assertSame($delegationPermissionId, (string) $result->delegationPermissionIdentifier());
        $this->assertSame($principalGroupId, (string) $result->principalGroupIdentifier());
        $this->assertSame($targetAccountId, (string) $result->targetAccountIdentifier());
        $this->assertSame($affiliationId, (string) $result->affiliationIdentifier());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: 指定したIDを持つDelegationPermissionが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->findById(new DelegationPermissionIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくAffiliationIdに紐づくDelegationPermissionを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAffiliationId(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId = StrTestHelper::generateUuid();
        $sourceAccountId = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($sourceAccountId),
        );
        CreateDelegationPermission::create(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
        );

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->findByAffiliationId(new AffiliationIdentifier($affiliationId));

        $this->assertNotNull($result);
        $this->assertSame($affiliationId, (string) $result->affiliationIdentifier());
    }

    /**
     * 正常系: 指定したAffiliationIdを持つDelegationPermissionが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAffiliationIdWhenNotFound(): void
    {
        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->findByAffiliationId(new AffiliationIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 指定したPrincipalGroupとtargetAccountに対するDelegationPermissionが存在する場合、trueが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsForAnyPrincipalGroup(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId = StrTestHelper::generateUuid();
        $sourceAccountId = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($sourceAccountId),
        );
        CreateDelegationPermission::create(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
        );

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->existsForAnyPrincipalGroup(
            [new PrincipalGroupIdentifier($principalGroupId)],
            new AccountIdentifier($targetAccountId),
        );

        $this->assertTrue($result);
    }

    /**
     * 正常系: 複数のPrincipalGroupのいずれかに対するDelegationPermissionが存在する場合、trueが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsForAnyPrincipalGroupWithMultipleGroups(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId1 = StrTestHelper::generateUuid();
        $principalGroupId2 = StrTestHelper::generateUuid();
        $sourceAccountId1 = StrTestHelper::generateUuid();
        $sourceAccountId2 = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId1);
        CreateAccount::create($sourceAccountId2);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId1),
            new AccountIdentifier($sourceAccountId1),
        );
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId2),
            new AccountIdentifier($sourceAccountId2),
        );

        // principalGroupId1のみに対するDelegationPermissionを作成
        CreateDelegationPermission::create(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId1),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
        );

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);
        $result = $repository->existsForAnyPrincipalGroup(
            [
                new PrincipalGroupIdentifier($principalGroupId1),
                new PrincipalGroupIdentifier($principalGroupId2),
            ],
            new AccountIdentifier($targetAccountId),
        );

        $this->assertTrue($result);
    }

    /**
     * 正常系: 指定したPrincipalGroupとtargetAccountに対するDelegationPermissionが存在しない場合、falseが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsForAnyPrincipalGroupWhenNotFound(): void
    {
        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);

        $result = $repository->existsForAnyPrincipalGroup(
            [new PrincipalGroupIdentifier(StrTestHelper::generateUuid())],
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 正しくDelegationPermissionを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $delegationPermissionId = StrTestHelper::generateUuid();
        $principalGroupId = StrTestHelper::generateUuid();
        $sourceAccountId = StrTestHelper::generateUuid();
        $targetAccountId = StrTestHelper::generateUuid();
        $affiliationId = StrTestHelper::generateUuid();

        CreateAccount::create($sourceAccountId);
        CreateAccount::create($targetAccountId);
        CreateAccountPrincipalGroup::create(
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($sourceAccountId),
        );
        CreateDelegationPermission::create(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
        );

        // 削除前に存在確認
        $this->assertDatabaseHas('delegation_permissions', ['id' => $delegationPermissionId]);

        $repository = $this->app->make(DelegationPermissionRepositoryInterface::class);

        // 削除対象のエンティティを直接作成
        $delegationPermission = new DelegationPermission(
            new DelegationPermissionIdentifier($delegationPermissionId),
            new PrincipalGroupIdentifier($principalGroupId),
            new AccountIdentifier($targetAccountId),
            new AffiliationIdentifier($affiliationId),
            new DateTimeImmutable(),
        );

        $repository->delete($delegationPermission);

        // 削除後の確認
        $this->assertDatabaseMissing('delegation_permissions', ['id' => $delegationPermissionId]);
    }
}
