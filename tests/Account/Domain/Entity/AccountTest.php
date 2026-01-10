<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Domain\ValueObject\AccountCategory;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\AddressLine;
use Source\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Domain\ValueObject\BillingContact;
use Source\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Domain\ValueObject\City;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Domain\ValueObject\ContractName;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Account\Domain\ValueObject\DeletionBlockReason;
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Domain\ValueObject\Phone;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
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
        $this->assertSame($dummyAccount->contractInfo, $dummyAccount->account->contractInfo());
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
        $address = new BillingAddress(
            countryCode: CountryCode::JAPAN,
            postalCode: new PostalCode('100-0001'),
            stateOrProvince: new StateOrProvince('Tokyo'),
            city: new City('Chiyoda'),
            addressLine1: new AddressLine('1-1-1'),
        );
        $contact = new BillingContact(
            name: new ContractName('Taro Example'),
            email: new Email('taro@example.com'),
            phone: new Phone('+81-3-0000-0000'),
        );
        $plan = new Plan(
            planName: new PlanName('Basic Plan'),
            billingCycle: BillingCycle::MONTHLY,
            planDescription: new PlanDescription(''),
            money: new Money(10000, Currency::KRW),
        );
        $taxInfo = new TaxInfo(TaxRegion::JP, TaxCategory::TAXABLE, 'T1234567890123');
        $contractInfo = new ContractInfo(
            billingAddress: $address,
            billingContact: $contact,
            billingMethod: BillingMethod::INVOICE,
            plan: $plan,
            taxInfo: $taxInfo,
        );

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $deletionReadiness ??= DeletionReadinessChecklist::ready();

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $accountCategory,
            $deletionReadiness,
        );

        return new AccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
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
        public ContractInfo $contractInfo,
        public AccountStatus $status,
        public AccountCategory $accountCategory,
        public Account $account,
        public DeletionReadinessChecklist $deletionReadiness,
    ) {
    }
}
