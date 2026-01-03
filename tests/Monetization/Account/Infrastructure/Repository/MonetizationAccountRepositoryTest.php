<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
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
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\CreateIdentity;
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
        $ownerIdentityId = StrTestHelper::generateUuid();

        // FK制約のためIdentityを事前に作成
        CreateIdentity::create(
            new IdentityIdentifier($ownerIdentityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('123-4567'),
            new StateOrProvince('Tokyo'),
            new City('Shibuya'),
            new AddressLine('1-2-3 Shibuya'),
            null,
            null,
        );

        $billingContact = new BillingContact(
            new ContractName('Test Contact'),
            new Email($email),
            null,
        );

        $plan = new Plan(
            new PlanName('Standard Plan'),
            BillingCycle::MONTHLY,
            new PlanDescription(''),
            new Money(0, Currency::JPY),
        );

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            null,
        );

        $contractInfo = new ContractInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::CREDIT_CARD,
            $plan,
            $taxInfo,
            null,
        );

        $memberships = [
            new AccountMembership(
                new IdentityIdentifier($ownerIdentityId),
                AccountRole::OWNER,
            ),
        ];

        $account = new Account(
            new AccountIdentifier($accountId),
            new Email($email),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            $contractInfo,
            AccountStatus::ACTIVE,
            $memberships,
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
            new StripeCustomerId('cus_1234567890abcdef'),
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
            new StripeConnectedAccountId('acct_1234567890abcdef'),
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
            new StripeCustomerId('cus_1234567890abcdef'),
            new StripeConnectedAccountId('acct_1234567890abcdef'),
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
        $account->linkStripeCustomer(new StripeCustomerId('cus_updated1234567'));
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
}
