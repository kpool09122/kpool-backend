<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Domain\Exception\AccountMembershipNotFoundException;
use Source\Account\Domain\Exception\DisallowedToWithdrawByOwnerException;
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
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
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
        $this->assertSame($dummyAccount->deletionReadiness, $dummyAccount->account->deletionReadiness());
    }

    /**
     * 正常系: 所有者のいないアカウントは作成できないこと.
     *
     * @return void
     */
    public function testNoOwner(): void
    {
        $memberships = [new AccountMembership(new UserIdentifier(StrTestHelper::generateUuid()), AccountRole::MEMBER)];
        $this->expectException(DomainException::class);
        $this->createDummyAccountTestData($memberships);
    }

    /**
     * 正常系: メンバーを正しく追加できること.
     *
     * @return void
     */
    public function testAttachMembership(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            new UserIdentifier(StrTestHelper::generateUuid()),
            AccountRole::MEMBER
        );

        $dummyAccount->account->attachMember($newMember);

        $this->assertContains($newMember, $dummyAccount->account->memberships());
    }

    /**
     * 異常系: 重複メンバーを正しく追加しようとした場合、例外をスローすること.
     *
     * @return void
     */
    public function testAttachMembershipThrowsDomainException(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            $dummyAccount->memberships[0]->userIdentifier(),
            AccountRole::MEMBER
        );

        $this->expectException(DomainException::class);
        $dummyAccount->account->attachMember($newMember);
    }

    /**
     * 正常系: メンバーを正しく削除できること.
     *
     * @return void
     */
    public function testDetachMembership(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $newMember = new AccountMembership(
            new UserIdentifier(StrTestHelper::generateUuid()),
            AccountRole::MEMBER
        );

        $dummyAccount->account->attachMember($newMember);

        $this->assertContains($newMember, $dummyAccount->account->memberships());

        $dummyAccount->account->detachMember($newMember);

        $this->assertNotContains($newMember, $dummyAccount->account->memberships());
    }

    /**
     * 異常系: アカウント所有者はdetachできないこと.
     *
     * @return void
     */
    public function testDetachMembershipWhenCorporateOwner(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $member = $dummyAccount->memberships[0];

        $this->expectException(DisallowedToWithdrawByOwnerException::class);

        $dummyAccount->account->detachMember($member);
    }

    /**
     * 異常系: アカウントに存在しないメンバーをdetachできないこと.
     *
     * @return void
     */
    public function testDetachMembershipWhenNotFound(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $nonMember = new AccountMembership(
            new UserIdentifier(StrTestHelper::generateUuid()),
            AccountRole::MEMBER
        );

        $this->expectException(AccountMembershipNotFoundException::class);

        $dummyAccount->account->detachMember($nonMember);
    }

    /**
     * 異常系: オーナーが入力上でMEMBERに偽装されてもdetachできないこと.
     *
     * @return void
     */
    public function testDetachMembershipWhenOwnerRoleSpoofed(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();
        $owner = $dummyAccount->memberships[0];

        $spoofedMembership = new AccountMembership(
            $owner->userIdentifier(),
            AccountRole::MEMBER
        );

        $this->expectException(DisallowedToWithdrawByOwnerException::class);

        $dummyAccount->account->detachMember($spoofedMembership);
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
        $dummyAccount->account->detachMember($dummyAccount->memberships[0]);
    }

    /**
     * 正常系: 削除に必要な前提条件を満たしていれば例外が発生しないこと.
     *
     * @return void
     */
    public function testAssertDeletable(): void
    {
        $dummyAccount = $this->createDummyAccountTestData();

        $dummyAccount->account->assertDeletable();
        $this->assertTrue($dummyAccount->account->deletionReadiness()->isReady());
    }

    /**
     * 異常系: 削除前提条件が不足している場合、理由とともに例外がスローされること.
     *
     * @return void
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

    /**
     * テスト用のダミーAccount情報
     *
     * @param AccountMembership[] $memberships
     * @return AccountTestData
     */
    private function createDummyAccountTestData(
        array $memberships = [],
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

        $userId = new UserIdentifier(StrTestHelper::generateUuid());
        if ($memberships === []) {
            $memberships = [new AccountMembership($userId, AccountRole::OWNER)];
        }

        $status = AccountStatus::ACTIVE;

        $deletionReadiness ??= DeletionReadinessChecklist::ready();

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $memberships,
            $deletionReadiness,
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
            $deletionReadiness,
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
        public DeletionReadinessChecklist $deletionReadiness,
    ) {
    }
}
