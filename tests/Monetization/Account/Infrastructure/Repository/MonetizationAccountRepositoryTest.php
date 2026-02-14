<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\AddressLine;
use Source\Monetization\Account\Domain\ValueObject\BillingAddress;
use Source\Monetization\Account\Domain\ValueObject\BillingContact;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\City;
use Source\Monetization\Account\Domain\ValueObject\ContractName;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\Phone;
use Source\Monetization\Account\Domain\ValueObject\PostalCode;
use Source\Monetization\Account\Domain\ValueObject\StateOrProvince;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\TaxCategory;
use Source\Monetization\Account\Domain\ValueObject\TaxInfo;
use Source\Monetization\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MonetizationAccountRepositoryTest extends TestCase
{
    /**
     * FK制約を満たすため、事前にAccountを作成するヘルパーメソッド
     */
    private function createAccountForTest(string $accountId): void
    {
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';

        $account = new Account(
            new AccountIdentifier($accountId),
            new Email($email),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );

        $this->app->make(AccountRepositoryInterface::class)->save($account);
    }

    /**
     * 正常系: 正しくIDに紐づくMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [Capability::PURCHASE],
            new PaymentCustomerId('cus_1234567890abcdef'),
            null,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $result->accountIdentifier());
        $this->assertTrue($result->hasCapability(Capability::PURCHASE));
        $this->assertSame('cus_1234567890abcdef', (string) $result->stripeCustomerId());
        $this->assertNull($result->stripeConnectedAccountId());
    }

    /**
     * 正常系: 指定したIDを持つMonetizationAccountが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findById(new MonetizationAccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 指定したAccount IDでMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdentifier(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [Capability::SELL, Capability::RECEIVE_PAYOUT],
            null,
            new ConnectedAccountId('acct_1234567890abcdef'),
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findByAccountIdentifier($accountIdentifier);

        $this->assertNotNull($result);
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertTrue($result->hasCapability(Capability::SELL));
        $this->assertTrue($result->hasCapability(Capability::RECEIVE_PAYOUT));
        $this->assertNull($result->stripeCustomerId());
        $this->assertSame('acct_1234567890abcdef', (string) $result->stripeConnectedAccountId());
    }

    /**
     * 正常系: 指定したAccount IDでMonetizationAccountが取得できない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAccountIdentifierWhenNotFound(): void
    {
        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findByAccountIdentifier(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規のMonetizationAccountを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewAccount(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [Capability::PURCHASE, Capability::SELL],
            new PaymentCustomerId('cus_1234567890abcdef'),
            new ConnectedAccountId('acct_1234567890abcdef'),
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $this->assertDatabaseHas('monetization_accounts', [
            'id' => $monetizationAccountId,
            'account_id' => (string) $accountIdentifier,
            'stripe_customer_id' => 'cus_1234567890abcdef',
            'stripe_connected_account_id' => 'acct_1234567890abcdef',
        ]);
    }

    /**
     * 正常系: 正しく既存のMonetizationAccountを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingAccount(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [],
            null,
            null,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        // 更新
        $account->grantCapability(Capability::PURCHASE);
        $account->linkStripeCustomer(new PaymentCustomerId('cus_updated1234567'));
        $repository->save($account);

        $this->assertDatabaseHas('monetization_accounts', [
            'id' => $monetizationAccountId,
            'stripe_customer_id' => 'cus_updated1234567',
        ]);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));
        $this->assertTrue($result->hasCapability(Capability::PURCHASE));
    }

    /**
     * 正常系: Capabilitiesが空の場合も正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithEmptyCapabilities(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [],
            null,
            null,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertEmpty($result->capabilities());
    }

    /**
     * 正常系: Billing情報を含むMonetizationAccountを保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();

        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('100-0001'),
            new StateOrProvince('東京都'),
            new City('千代田区'),
            new AddressLine('丸の内1-1-1'),
            new AddressLine('ビル名 10F'),
        );

        $billingContact = new BillingContact(
            new ContractName('山田 太郎'),
            new Email('billing@example.com'),
            new Phone('+81312345678'),
        );

        $billingMethod = BillingMethod::CREDIT_CARD;

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            'T1234567890123',
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [Capability::PURCHASE],
            new PaymentCustomerId('cus_billing_test'),
            null,
            $billingAddress,
            $billingContact,
            $billingMethod,
            $taxInfo,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);

        // BillingAddress の検証
        $this->assertNotNull($result->billingAddress());
        $this->assertSame(CountryCode::JAPAN, $result->billingAddress()->countryCode());
        $this->assertSame('100-0001', (string) $result->billingAddress()->postalCode());
        $this->assertSame('東京都', (string) $result->billingAddress()->stateOrProvince());
        $this->assertSame('千代田区', (string) $result->billingAddress()->city());
        $this->assertSame('丸の内1-1-1', (string) $result->billingAddress()->addressLine1());
        $this->assertSame('ビル名 10F', (string) $result->billingAddress()->addressLine2());
        $this->assertNull($result->billingAddress()->addressLine3());

        // BillingContact の検証
        $this->assertNotNull($result->billingContact());
        $this->assertSame('山田 太郎', (string) $result->billingContact()->name());
        $this->assertSame('billing@example.com', (string) $result->billingContact()->email());
        $this->assertSame('+81312345678', (string) $result->billingContact()->phone());

        // BillingMethod の検証
        $this->assertSame(BillingMethod::CREDIT_CARD, $result->billingMethod());

        // TaxInfo の検証
        $this->assertNotNull($result->taxInfo());
        $this->assertSame(TaxRegion::JP, $result->taxInfo()->region());
        $this->assertSame(TaxCategory::TAXABLE, $result->taxInfo()->category());
        $this->assertSame('T1234567890123', $result->taxInfo()->taxCode());
    }

    /**
     * 正常系: Billing情報を更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testUpdateBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();

        // Billing情報なしで作成
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [],
            null,
            null,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        // Billing情報を更新
        $billingAddress = new BillingAddress(
            CountryCode::UNITED_STATES,
            new PostalCode('10001'),
            new StateOrProvince('New York'),
            new City('New York'),
            new AddressLine('123 Main Street'),
        );

        $billingContact = new BillingContact(
            new ContractName('John Doe'),
            new Email('john@example.com'),
        );

        $account->setBillingInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::INVOICE,
            new TaxInfo(TaxRegion::US, TaxCategory::EXEMPT),
        );

        $repository->save($account);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertNotNull($result->billingAddress());
        $this->assertSame(CountryCode::UNITED_STATES, $result->billingAddress()->countryCode());
        $this->assertSame('10001', (string) $result->billingAddress()->postalCode());

        $this->assertNotNull($result->billingContact());
        $this->assertSame('John Doe', (string) $result->billingContact()->name());
        $this->assertNull($result->billingContact()->phone());

        $this->assertSame(BillingMethod::INVOICE, $result->billingMethod());

        $this->assertNotNull($result->taxInfo());
        $this->assertSame(TaxRegion::US, $result->taxInfo()->region());
        $this->assertSame(TaxCategory::EXEMPT, $result->taxInfo()->category());
        $this->assertNull($result->taxInfo()->taxCode());
    }

    /**
     * 正常系: Billing情報が部分的に設定されている場合も正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindWithPartialBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $this->createAccountForTest($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();

        // BillingAddressとBillingMethodのみ設定
        $billingAddress = new BillingAddress(
            CountryCode::KOREA_REPUBLIC,
            new PostalCode('06236'),
            new StateOrProvince('서울특별시'),
            new City('강남구'),
            new AddressLine('테헤란로 123'),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [Capability::SELL],
            null,
            new ConnectedAccountId('acct_partial_test'),
            $billingAddress,
            null,
            BillingMethod::BANK_TRANSFER,
            null,
        );

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);

        // BillingAddress はあり
        $this->assertNotNull($result->billingAddress());
        $this->assertSame(CountryCode::KOREA_REPUBLIC, $result->billingAddress()->countryCode());

        // BillingContact はなし
        $this->assertNull($result->billingContact());

        // BillingMethod はあり
        $this->assertSame(BillingMethod::BANK_TRANSFER, $result->billingMethod());

        // TaxInfo はなし
        $this->assertNull($result->taxInfo());
    }
}
