<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestAccount;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ContactAddress;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Phone;
use Tests\Helper\StrTestHelper;

class AccountCategoryChangeRequestAccountTest extends TestCase
{
    public function testCapturesAccountValuesAndRemainsUnchangedAfterProfileEdits(): void
    {
        $account = $this->account();
        $snapshot = AccountCategoryChangeRequestAccount::fromAccount($account);
        $account->changeName(new AccountName('Updated'));
        $account->changePhone(null);
        $account->changeAddress(null);

        $this->assertSame('Applicant', $snapshot->name);
        $this->assertSame('applicant@example.com', $snapshot->email);
        $this->assertSame('corporation', $snapshot->type);
        $this->assertSame('active', $snapshot->status);
        $this->assertSame('+33123456789', $snapshot->phone);
        $this->assertSame('FR', $snapshot->countryCode);
        $this->assertSame('1 rue de Paris', $snapshot->addressLine1);
        $this->assertSame('75001', $snapshot->postalCode);
        $this->assertSame('Paris', $snapshot->locality);
        $this->assertNull($snapshot->administrativeAreaCode);
        $this->assertNull($snapshot->addressLine2);
    }

    public function testPreservesMissingLocalityAsNull(): void
    {
        $account = $this->account();
        $account->changeAddress(ContactAddress::fromArray(['countryCode' => 'FR', 'postalCode' => '75001', 'addressLine1' => '1 rue de Paris']));
        $this->assertNull(AccountCategoryChangeRequestAccount::fromAccount($account)->locality);
    }

    public function testPreservesProvidedAddressValues(): void
    {
        $account = $this->account();
        $account->changeAddress(ContactAddress::fromArray([
            'countryCode' => 'JP', 'administrativeAreaCode' => '13', 'postalCode' => '100-0001',
            'locality' => '千代田区', 'addressLine1' => '千代田1-1', 'addressLine2' => '1F',
        ]));
        $snapshot = AccountCategoryChangeRequestAccount::fromAccount($account);
        $this->assertSame('13', $snapshot->administrativeAreaCode);
        $this->assertSame('100-0001', $snapshot->postalCode);
        $this->assertSame('千代田区', $snapshot->locality);
        $this->assertSame('千代田1-1', $snapshot->addressLine1);
        $this->assertSame('1F', $snapshot->addressLine2);
    }

    /** @return iterable<string, array{string}> */
    public static function missingFields(): iterable
    {
        foreach (['type', 'phone', 'address', 'country', 'postalCode', 'addressLine1'] as $field) {
            yield $field => [$field];
        }
    }

    #[DataProvider('missingFields')]
    public function testRejectsMissingRequiredFields(string $field): void
    {
        $account = $this->account($field === 'type' ? null : AccountType::CORPORATION);
        if ($field === 'phone') {
            $account->changePhone(null);
        } elseif ($field === 'address') {
            $account->changeAddress(null);
        } elseif ($field === 'postalCode') {
            $address = ['countryCode' => 'FR', 'postalCode' => '75001', 'locality' => 'Paris', 'addressLine1' => '1 rue de Paris'];
            $address[$field] = null;
            $account->changeAddress(ContactAddress::fromArray($address));
        } elseif ($field === 'country') {
            $account->changeAddress(ContactAddress::fromArray(['addressLine1' => 'Address']));
        } elseif ($field === 'addressLine1') {
            $account->changeAddress(ContactAddress::fromArray(['countryCode' => 'FR']));
        }
        $this->expectException(InvalidArgumentException::class);
        AccountCategoryChangeRequestAccount::fromAccount($account);
    }

    private function account(?AccountType $accountType = AccountType::CORPORATION): Account
    {
        return new Account(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('applicant@example.com'),
            $accountType,
            new AccountName('Applicant'),
            $accountType === null ? AccountStatus::PENDING : AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
            new NotificationSettings([]),
            new Phone('+33123456789'),
            ContactAddress::fromArray(['countryCode' => 'FR', 'postalCode' => '75001', 'locality' => 'Paris', 'addressLine1' => '1 rue de Paris']),
        );
    }
}
