<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteAccountOutputTest extends TestCase
{
    public function testToArrayPreservesUnselectedAccountType(): void
    {
        $account = new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('pending@example.com'),
            null,
            new AccountName('Pending Account'),
            AccountStatus::PENDING,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );
        $output = new DeleteAccountOutput();
        $output->setAccount($account);
        $result = $output->toArray();

        self::assertTrue(array_key_exists('type', $result));
        $this->assertNull($result['type']);
    }

    /**
     * 正常系: AccountがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithAccount(): void
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@example.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');
        $status = AccountStatus::ACTIVE;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $status,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );

        $output = new DeleteAccountOutput();
        $output->setAccount($account);

        $result = $output->toArray();

        self::assertTrue(array_key_exists('accountIdentifier', $result));
        $this->assertSame((string) $identifier, $result['accountIdentifier']);
        $this->assertSame((string) $email, $result['email']);
        $this->assertSame($accountType->value, $result['type']);
        $this->assertSame((string) $accountName, $result['name']);
        $this->assertSame($status->value, $result['status']);
    }

    /**
     * 正常系: Accountが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutAccount(): void
    {
        $output = new DeleteAccountOutput();
        $this->assertSame([], $output->toArray());
    }
}
