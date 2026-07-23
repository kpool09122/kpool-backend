<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\UpdateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;

class UpdateAccountOutputTest extends TestCase
{
    public function testToArrayReturnsAccountSummary(): void
    {
        $account = new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            AccountType::CORPORATION,
            new AccountName('Updated Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );
        $output = new UpdateAccountOutput();

        $output->setAccount($account);
        $result = $output->toArray();

        $this->assertSame((string) $account->accountIdentifier(), $result['accountIdentifier']);
        $this->assertSame('Updated Account', $result['name']);
    }

    public function testToArrayReturnsEmptyArrayWhenAccountIsNotSet(): void
    {
        $this->assertSame([], (new UpdateAccountOutput())->toArray());
    }
}
