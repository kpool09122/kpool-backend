<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\AddressLine;
use Source\Monetization\Account\Domain\ValueObject\BillingAddress;
use Source\Monetization\Account\Domain\ValueObject\BillingContact;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\City;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ContractName;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\Phone;
use Source\Monetization\Account\Domain\ValueObject\PostalCode;
use Source\Monetization\Account\Domain\ValueObject\StateOrProvince;
use Source\Monetization\Account\Domain\ValueObject\TaxCategory;
use Source\Monetization\Account\Domain\ValueObject\TaxInfo;
use Source\Monetization\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MonetizationAccountRepositoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // find 系テスト（ヘルパーでデータ挿入）
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しくIDに紐づくMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '["purchase"]',
            'stripe_customer_id' => 'cus_1234567890abcdef',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
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
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '["sell","receive_payout"]',
            'stripe_connected_account_id' => 'acct_1234567890abcdef',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findByAccountIdentifier(new AccountIdentifier($accountId));

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
     * 正常系: 指定したConnectedAccountIdでMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByConnectedAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '["sell","receive_payout"]',
            'stripe_connected_account_id' => 'acct_findtest1234567',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findByConnectedAccountId(new ConnectedAccountId('acct_findtest1234567'));

        $this->assertNotNull($result);
        $this->assertSame($monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertTrue($result->hasCapability(Capability::SELL));
        $this->assertTrue($result->hasCapability(Capability::RECEIVE_PAYOUT));
        $this->assertNull($result->stripeCustomerId());
        $this->assertSame('acct_findtest1234567', (string) $result->stripeConnectedAccountId());
    }

    /**
     * 正常系: 指定したConnectedAccountIdでMonetizationAccountが取得できない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByConnectedAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findByConnectedAccountId(new ConnectedAccountId('acct_nonexistent12345'));

        $this->assertNull($result);
    }

    /**
     * 正常系: Capabilitiesが空のMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithEmptyCapabilities(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '[]',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertEmpty($result->capabilities());
    }

    /**
     * 正常系: Billing情報を含むMonetizationAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '["purchase"]',
            'stripe_customer_id' => 'cus_billing_test',
            'billing_address' => [
                'country_code' => 'JP',
                'postal_code' => '100-0001',
                'state_or_province' => '東京都',
                'city' => '千代田区',
                'address_line1' => '丸の内1-1-1',
                'address_line2' => 'ビル名 10F',
            ],
            'billing_contact' => [
                'name' => '山田 太郎',
                'email' => 'billing@example.com',
                'phone' => '+81312345678',
            ],
            'billing_method' => 'credit_card',
            'tax_info' => [
                'region' => 'JP',
                'category' => 'taxable',
                'tax_code' => 'T1234567890123',
            ],
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
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
     * 正常系: Billing情報が部分的に設定されている場合も正しく取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithPartialBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '["sell"]',
            'stripe_connected_account_id' => 'acct_partial_test',
            'billing_address' => [
                'country_code' => 'KR',
                'postal_code' => '06236',
                'state_or_province' => '서울특별시',
                'city' => '강남구',
                'address_line1' => '테헤란로 123',
            ],
            'billing_method' => 'bank_transfer',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
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

    // -------------------------------------------------------------------------
    // save 系テスト（repository->save() を使用）
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しく新規のMonetizationAccountを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewAccount(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
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
        CreateAccount::create($accountId);
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
     * 正常系: Billing情報を含むMonetizationAccountを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithBillingInfo(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $accountIdentifier = new AccountIdentifier($accountId);

        $monetizationAccountId = StrTestHelper::generateUuid();
        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            $accountIdentifier,
            [],
            null,
            null,
        );
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
        $account->setBillingInfo($billingAddress, $billingContact, BillingMethod::CREDIT_CARD, null);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $repository->save($account);

        // 再取得して検証
        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertSame(BillingMethod::CREDIT_CARD, $result->billingMethod());

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
        $monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($accountId);
        CreateMonetizationAccount::create($monetizationAccountId, [
            'account_id' => $accountId,
            'capabilities' => '[]',
        ]);

        $repository = $this->app->make(MonetizationAccountRepositoryInterface::class);
        $account = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));

        // Billing情報を更新
        $account->setBillingMethod(BillingMethod::INVOICE);
        $account->setTaxInfo(new TaxInfo(TaxRegion::US, TaxCategory::EXEMPT));
        $repository->save($account);

        $this->assertDatabaseHas('monetization_accounts', [
            'id' => $monetizationAccountId,
            'billing_method' => 'invoice',
        ]);

        $result = $repository->findById(new MonetizationAccountIdentifier($monetizationAccountId));
        $this->assertSame(BillingMethod::INVOICE, $result->billingMethod());
        $this->assertSame(TaxRegion::US, $result->taxInfo()->region());
        $this->assertSame(TaxCategory::EXEMPT, $result->taxInfo()->category());
    }
}
