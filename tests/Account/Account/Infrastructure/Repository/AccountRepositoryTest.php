<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocument;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
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
        ?AccountDocuments $documents = null,
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
            $documents ?? new AccountDocuments(),
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
     * 正常系: 保存済みAccountのnameを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesAccountName(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $account->changeName(new AccountName('Updated Account'));
        $repository->save($account);

        $result = $repository->findById(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame('Updated Account', (string) $result->name());
        $this->assertDatabaseHas('accounts', [
            'id' => $accountId,
            'name' => 'Updated Account',
        ]);
    }

    /**
     * 正常系: Account保存時にAccountDocumentを全削除して再作成できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveRecreatesAccountDocuments(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $repository = $this->app->make(AccountRepositoryInterface::class);
        $account = $this->createTestAccount(
            accountId: $accountId,
            documents: new AccountDocuments([
                new AccountDocument(
                    new AccountIdentifier($accountId),
                    DocumentType::BUSINESS_REGISTRATION,
                    new DocumentPath('accounts/documents/old_business_registration.pdf'),
                    new DateTimeImmutable('2026-07-01 00:00:00'),
                ),
                new AccountDocument(
                    new AccountIdentifier($accountId),
                    DocumentType::REPRESENTATIVE_ID,
                    new DocumentPath('accounts/documents/old_representative_id.jpg'),
                    new DateTimeImmutable('2026-07-01 00:00:00'),
                ),
            ]),
        );
        $repository->save($account);

        $account->replaceDocuments([
            new AccountDocument(
                new AccountIdentifier($accountId),
                DocumentType::REPRESENTATIVE_ID,
                new DocumentPath('accounts/documents/new_representative_id.jpg'),
                new DateTimeImmutable('2026-07-02 00:00:00'),
            ),
        ]);
        $repository->save($account);

        $this->assertDatabaseMissing('account_documents', [
            'account_id' => $accountId,
            'document_type' => 'business_registration',
        ]);
        $this->assertDatabaseMissing('account_documents', [
            'account_id' => $accountId,
            'document_path' => 'accounts/documents/old_representative_id.jpg',
        ]);
        $this->assertDatabaseHas('account_documents', [
            'account_id' => $accountId,
            'document_type' => 'representative_id',
            'document_path' => 'accounts/documents/new_representative_id.jpg',
        ]);

        $account->replaceDocuments([]);
        $repository->save($account);

        $this->assertDatabaseMissing('account_documents', [
            'account_id' => $accountId,
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
