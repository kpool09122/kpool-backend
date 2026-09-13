<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalRepositoryTest extends TestCase
{
    /**
     * 正常系: 正しくIDに紐づくプリンシパルを取得できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findById($principalIdentifier);

        $this->assertNotNull($result);
        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $result->accountIdentifier());
    }

    /**
     * 正常系: 指定したIDを持つプリンシパルが存在しない場合、NULLが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findById(new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 指定したIdentityIDでプリンシパルが作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierAndAccountIdentifier(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifierAndAccountIdentifier($identityIdentifier, $accountIdentifier);

        $this->assertNotNull($result);
        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $result->accountIdentifier());
    }

    /**
     * 正常系: 指定したIdentity IDでプリンシパルが取得できない場合、NULLが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierAndAccountIdentifierWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findByIdentityIdentifierAndAccountIdentifier(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 同じ Identity に紐づく複数 Account のプリンシパルをすべて取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testFindByIdentityIdentifierReturnsPrincipalsForAllAccounts(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        CreateAccount::create((string) $otherAccountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $otherPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);
        CreatePrincipal::create($otherPrincipalIdentifier, $identityIdentifier, $otherAccountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByIdentityIdentifier($identityIdentifier);

        $this->assertCount(2, $results);
        $expectedAccountIdentifiers = [
            (string) $accountIdentifier,
            (string) $otherAccountIdentifier,
        ];
        $actualAccountIdentifiers = array_map(
            static fn (Principal $principal): string => (string) $principal->accountIdentifier(),
            $results,
        );
        sort($expectedAccountIdentifiers);
        sort($actualAccountIdentifiers);

        $this->assertSame($expectedAccountIdentifiers, $actualAccountIdentifiers);
    }

    /**
     * 正常系: 正しく新規のプリンシパルを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalId = StrTestHelper::generateUuid();
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal(
            new PrincipalIdentifier($principalId),
            $identityIdentifier,
            $accountIdentifier,
            $delegationIdentifier,
        );

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => $principalId,
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'delegation_identifier' => (string) $delegationIdentifier,
        ]);

        $saved = $repository->findById(new PrincipalIdentifier($principalId));
        $this->assertNotNull($saved);
        $this->assertSame((string) $identityIdentifier, (string) $saved->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $saved->accountIdentifier());
    }

    /**
     * 正常系: 正しく既存のプリンシパルを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSaveWithExistingPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);


        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $accountIdentifier,
        );

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->save($principal);

        $this->assertDatabaseHas('wiki_principals', [
            'id' => (string) $principalIdentifier,
        ]);
    }

    /**
     * 正常系: 正しくDelegation IDに紐づくプリンシパルを取得できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testFindByDelegation(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier, [
            'delegation_identifier' => (string) $delegationIdentifier,
        ]);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByDelegation($delegationIdentifier);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $result->accountIdentifier());
        $this->assertSame((string) $delegationIdentifier, (string) $result->delegationIdentifier());
    }

    /**
     * 正常系: 同じ Delegation ID に紐づく複数 Account のプリンシパルをすべて取得できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testFindByDelegationReturnsPrincipalsForAllAccounts(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        CreateAccount::create((string) $otherAccountIdentifier);

        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
            $accountIdentifier,
            ['delegation_identifier' => (string) $delegationIdentifier],
        );
        CreatePrincipal::create(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
            $otherAccountIdentifier,
            ['delegation_identifier' => (string) $delegationIdentifier],
        );

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByDelegation($delegationIdentifier);

        $expectedAccountIdentifiers = [
            (string) $accountIdentifier,
            (string) $otherAccountIdentifier,
        ];
        $actualAccountIdentifiers = array_map(
            static fn (Principal $principal): string => (string) $principal->accountIdentifier(),
            $results,
        );
        sort($expectedAccountIdentifiers);
        sort($actualAccountIdentifiers);

        $this->assertSame($expectedAccountIdentifiers, $actualAccountIdentifiers);
    }

    /**
     * 正常系: 指定したDelegation IDを持つプリンシパルが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByDelegationWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $result = $repository->findByDelegation(new DelegationIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくDelegation IDに紐づくプリンシパルを削除できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testDeleteByDelegation(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier, [
            'delegation_identifier' => (string) $delegationIdentifier,
        ]);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->deleteByDelegation($delegationIdentifier);

        $this->assertDatabaseMissing('wiki_principals', [
            'id' => (string) $principalIdentifier,
        ]);
    }

    /**
     * 正常系: 存在しないDelegation IDを指定しても例外が発生しないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteByDelegationWhenNotFound(): void
    {
        $this->expectNotToPerformAssertions();

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $repository->deleteByDelegation(new DelegationIdentifier(StrTestHelper::generateUuid()));
    }

    /**
     * 正常系: 正しくAccount IDに紐づくプリンシパルを取得できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     * @return void
     */
    #[Group('useDb')]
    public function testFindByAccountId(): void
    {
        $identityIdentifier1 = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier1, [
            'email' => 'test1@example.com',
            'identity_name' => 'test-identity-1',
        ]);

        $identityIdentifier2 = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier2, [
            'email' => 'test2@example.com',
            'identity_name' => 'test-identity-2',
        ]);

        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier1 = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier1, $identityIdentifier1, $accountIdentifier);

        $principalIdentifier2 = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier2, $identityIdentifier2, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByAccountId($accountIdentifier);

        $this->assertCount(2, $results);
        $principalIds = array_map(fn ($p) => (string) $p->principalIdentifier(), $results);
        $this->assertContains((string) $principalIdentifier1, $principalIds);
        $this->assertContains((string) $principalIdentifier2, $principalIds);
    }

    /**
     * 正常系: 指定したAccount IDを持つプリンシパルが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByAccountId(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 正常系: findByIdsで複数のプリンシパルを取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsMultiplePrincipals(): void
    {
        $identityIdentifier1 = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier1, [
            'email' => 'findbyids1@example.com',
            'identity_name' => 'findbyids-identity-1',
        ]);

        $identityIdentifier2 = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier2, [
            'email' => 'findbyids2@example.com',
            'identity_name' => 'findbyids-identity-2',
        ]);

        $identityIdentifier3 = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier3, [
            'email' => 'findbyids3@example.com',
            'identity_name' => 'findbyids-identity-3',
        ]);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $principalIdentifier1 = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier2 = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier3 = new PrincipalIdentifier(StrTestHelper::generateUuid());

        CreatePrincipal::create($principalIdentifier1, $identityIdentifier1, $accountIdentifier);
        CreatePrincipal::create($principalIdentifier2, $identityIdentifier2, $accountIdentifier);
        CreatePrincipal::create($principalIdentifier3, $identityIdentifier3, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByIds([$principalIdentifier1, $principalIdentifier2, $principalIdentifier3]);

        $this->assertCount(3, $results);
        $this->assertContainsOnlyInstancesOf(Principal::class, $results);
        $this->assertArrayHasKey((string) $principalIdentifier1, $results);
        $this->assertArrayHasKey((string) $principalIdentifier2, $results);
        $this->assertArrayHasKey((string) $principalIdentifier3, $results);
        $this->assertSame((string) $principalIdentifier1, (string) $results[(string) $principalIdentifier1]->principalIdentifier());
        $this->assertSame((string) $principalIdentifier2, (string) $results[(string) $principalIdentifier2]->principalIdentifier());
        $this->assertSame((string) $principalIdentifier3, (string) $results[(string) $principalIdentifier3]->principalIdentifier());
    }

    /**
     * 正常系: findByIdsで空の配列を渡した場合に空の配列を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsEmptyArrayWhenEmptyInput(): void
    {
        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByIds([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 正常系: findByIdsで存在しないプリンシパルは結果に含まれないこと.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsOnlyExistingPrincipals(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, [
            'email' => 'existing-principal@example.com',
            'identity_name' => 'existing-principal-user',
        ]);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);

        $existingPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $nonExistingPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());

        CreatePrincipal::create($existingPrincipalId, $identityIdentifier, $accountIdentifier);

        $repository = $this->app->make(PrincipalRepositoryInterface::class);
        $results = $repository->findByIds([$existingPrincipalId, $nonExistingPrincipalId]);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey((string) $existingPrincipalId, $results);
        $this->assertArrayNotHasKey((string) $nonExistingPrincipalId, $results);
        $this->assertSame((string) $existingPrincipalId, (string) $results[(string) $existingPrincipalId]->principalIdentifier());
    }
}
