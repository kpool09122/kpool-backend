<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountIdentifier;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
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
use Source\Account\Domain\ValueObject\Currency;
use Source\Account\Domain\ValueObject\Money;
use Source\Account\Domain\ValueObject\Phone;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class AccountTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $this->assertSame((string)$dummyAccount->identifier, (string)$dummyAccount->account->accountIdentifier());
        $this->assertSame((string)$dummyAccount->email, (string)$dummyAccount->account->email());
        $this->assertSame($dummyAccount->accountType, $dummyAccount->account->type());
        $this->assertSame((string)$dummyAccount->accountName, (string)$dummyAccount->account->name());
        $this->assertSame($dummyAccount->contractInfo, $dummyAccount->account->contractInfo());
        $this->assertSame($dummyAccount->status, $dummyAccount->account->status());
        $this->assertSame($dummyAccount->memberships, $dummyAccount->account->memberships());
    }

    /**
     * 正常系: ユーザーを正しく追加できること.
     *
     * @return void
     */
    public function testAttachUser(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            new UserIdentifier(StrTestHelper::generateUlid()),
            AccountRole::MEMBER
        );

        $dummyAccount->account->attachUser($newMember);

        $this->assertContains($newMember, $dummyAccount->account->memberships());
    }

    /**
     * 異常系: 重複ユーザーを正しく追加しようとした場合、例外をスローすること.
     *
     * @return void
     */
    public function testAttachUserThrowsDomainException(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            $dummyAccount->memberships[0]->userIdentifier(),
            AccountRole::MEMBER
        );

        $this->expectException(DomainException::class);
        $dummyAccount->account->attachUser($newMember);
    }

    /**
     * 正常系: ユーザーを正しく削除できること.
     *
     * @return void
     */
    public function testDetachUser(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            new UserIdentifier(StrTestHelper::generateUlid()),
            AccountRole::MEMBER
        );

        $dummyAccount->account->attachUser($newMember);

        $this->assertContains($newMember, $dummyAccount->account->memberships());

        $dummyAccount->account->detachUser($newMember);

        $this->assertNotContains($newMember, $dummyAccount->account->memberships());
    }

    /**
     * 異常系: アカウント所有者を削除しようとした場合、例外がスローされること.
     *
     * @return void
     */
    public function testDetachUserThrowsDomainException(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $this->expectException(DomainException::class);
        $dummyAccount->account->detachUser($dummyAccount->memberships[0]);
    }

    /**
     * テスト用のダミーAccount情報
     *
     * @return AccountTestData
     */
    private function createDummyAccountTestData(): AccountTestData
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUlid());
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

        $userId = new UserIdentifier(StrTestHelper::generateUlid());
        $memberships = [new AccountMembership($userId, AccountRole::OWNER)];

        $status = AccountStatus::ACTIVE;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $memberships,
        );


        return new AccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $memberships,
            $account,
        );
    }
}

/**
 * テスト用のAccountデータ
 */
readonly class AccountTestData
{
    /**
     * @param AccountMembership[] $memberships
     */
    public function __construct(
        public AccountIdentifier $identifier,
        public Email $email,
        public AccountType $accountType,
        public AccountName $accountName,
        public ContractInfo $contractInfo,
        public AccountStatus $status,
        public array $memberships,
        public Account $account,
    ) {
    }
}
