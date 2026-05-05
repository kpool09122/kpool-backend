<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionBlockReason;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;

class AccountTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     */
    public function test__construct(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $this->assertSame((string) $dummyAccount->identifier, (string) $dummyAccount->account->accountIdentifier());
        $this->assertSame((string) $dummyAccount->email, (string) $dummyAccount->account->email());
        $this->assertSame($dummyAccount->accountType, $dummyAccount->account->type());
        $this->assertSame((string) $dummyAccount->accountName, (string) $dummyAccount->account->name());
        $this->assertSame($dummyAccount->status, $dummyAccount->account->status());
        $this->assertSame($dummyAccount->deletionReadiness, $dummyAccount->account->deletionReadiness());
    }

    /**
     * 正常系: 正しくアカウントカテゴリーを変更できること.
     */
    public function testSetAccountCategory(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $this->assertSame($dummyAccount->accountCategory, $dummyAccount->account->accountCategory());

        $newCategory = AccountCategory::AGENCY;
        $dummyAccount->account->setAccountCategory($newCategory);

        $this->assertSame($newCategory, $dummyAccount->account->accountCategory());
        $this->assertNotSame($dummyAccount->accountCategory, $dummyAccount->account->accountCategory());
    }

    /**
     * 正常系: 削除に必要な前提条件を満たしていれば例外が発生しないこと.
     */
    public function testAssertDeletable(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $dummyAccount->account->assertDeletable();
        $this->assertTrue($dummyAccount->account->deletionReadiness()->isReady());
    }

    /**
     * 異常系: 削除前提条件が不足している場合、理由とともに例外がスローされること.
     */
    public function testAssertDeletableThrowsWhenNotReady(): void
    {
        $deletionReadiness = DeletionReadinessChecklist::fromReasons(
            DeletionBlockReason::UNPAID_INVOICES,
            DeletionBlockReason::OWNERSHIP_UNCONFIRMED,
            DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
        );

        $dummyAccount = $this->createDummyAccountTestData(deletionReadiness: $deletionReadiness);

        $this->expectException(AccountDeletionBlockedException::class);

        try {
            $dummyAccount->account->assertDeletable();
            $this->fail('AccountDeletionBlockedException was not thrown.');
        } catch (AccountDeletionBlockedException $exception) {
            $this->assertEquals(
                [
                    DeletionBlockReason::UNPAID_INVOICES,
                    DeletionBlockReason::OWNERSHIP_UNCONFIRMED,
                    DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
                ],
                $exception->blockers()
            );

            throw $exception;
        }
    }

    private function createDummyAccountTestData(
        ?DeletionReadinessChecklist $deletionReadiness = null
    ): AccountTestData {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@test.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $deletionReadiness ??= DeletionReadinessChecklist::ready();

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $status,
            $accountCategory,
            $deletionReadiness,
        );

        return new AccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $status,
            $accountCategory,
            $account,
            $deletionReadiness,
        );
    }
}

readonly class AccountTestData
{
    public function __construct(
        public AccountIdentifier $identifier,
        public Email $email,
        public AccountType $accountType,
        public AccountName $accountName,
        public AccountStatus $status,
        public AccountCategory $accountCategory,
        public Account $account,
        public DeletionReadinessChecklist $deletionReadiness,
    ) {
    }
}
