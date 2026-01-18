<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountRepositoryTest extends TestCase
{
    private function createTestAccount(
        ?string $accountId = null,
        ?string $email = null,
    ): Account {
        $accountId ??= StrTestHelper::generateUuid();
        $email ??= StrTestHelper::generateSmallAlphaStr(10) . '@example.com';

        return new Account(
            new AccountIdentifier($accountId),
            new Email($email),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );
    }

    /**
     * 正常系: 正しくIDに紐づくAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame((string) $account->email(), (string) $result->email());
        $this->assertSame($account->type(), $result->type());
        $this->assertSame((string) $account->name(), (string) $result->name());
        $this->assertSame($account->status(), $result->status());
    }

    /**
     * 正常系: 指定したIDを持つAccountが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(AccountRepositoryInterface::class);
        $result = $repository->findById(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくEmailに紐づくAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmail(): void
    {
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $account = $this->createTestAccount(email: $email);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findByEmail(new Email($email));

        $this->assertNotNull($result);
        $this->assertSame($email, (string) $result->email());
        $this->assertSame((string) $account->accountIdentifier(), (string) $result->accountIdentifier());
    }

    /**
     * 正常系: 指定したEmailを持つAccountが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailWhenNotFound(): void
    {
        $repository = $this->app->make(AccountRepositoryInterface::class);
        $result = $repository->findByEmail(new Email('notfound@example.com'));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規のAccountを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewAccount(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $account = $this->createTestAccount(accountId: $accountId, email: $email);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $this->assertDatabaseHas('accounts', [
            'id' => $accountId,
            'email' => $email,
            'type' => 'corporation',
            'name' => 'Test Account',
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: 正しくAccountを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        // 削除前に存在確認
        $this->assertNotNull($repository->findById(new AccountIdentifier($accountId)));

        // 削除
        $repository->delete($account);

        // 削除後の確認
        $this->assertNull($repository->findById(new AccountIdentifier($accountId)));
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
    }
}
