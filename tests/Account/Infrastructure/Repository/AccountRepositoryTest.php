<?php

declare(strict_types=1);

namespace Tests\Account\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
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
use Tests\TestCase;

class AccountRepositoryTest extends TestCase
{
    private function createTestAccount(
        ?string $accountId = null,
        ?string $email = null,
        ?DateTimeImmutable $billingStartDate = new DateTimeImmutable('2024-01-01 00:00:00'),
    ): Account {
        $accountId ??= StrTestHelper::generateUuid();
        $email ??= StrTestHelper::generateSmallAlphaStr(10) . '@example.com';

        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('123-4567'),
            new StateOrProvince('Tokyo'),
            new City('Shibuya'),
            new AddressLine('1-2-3 Shibuya'),
            new AddressLine('Building A'),
            null,
        );

        $billingContact = new BillingContact(
            new ContractName('Test Contact'),
            new Email($email),
            new Phone('+819012345678'),
        );

        $plan = new Plan(
            new PlanName('Standard Plan'),
            BillingCycle::MONTHLY,
            new PlanDescription('Standard monthly plan'),
            new Money(1000, Currency::JPY),
        );

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            'TAX001',
        );

        $contractInfo = new ContractInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::CREDIT_CARD,
            $plan,
            $taxInfo,
            $billingStartDate,
        );

        return new Account(
            new AccountIdentifier($accountId),
            new Email($email),
            AccountType::CORPORATION,
            new AccountName('Test Account'),
            $contractInfo,
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
        );
    }

    /**
     * 正常系: 正しくIDに紐づくAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame((string) $account->email(), (string) $result->email());
        $this->assertSame($account->type(), $result->type());
        $this->assertSame((string) $account->name(), (string) $result->name());
        $this->assertSame($account->status(), $result->status());
    }

    /**
     * 正常系: 指定したIDを持つAccountが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(AccountRepositoryInterface::class);
        $result = $repository->findById(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しくEmailに紐づくAccountを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmail(): void
    {
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $account = $this->createTestAccount(email: $email);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findByEmail(new Email($email));

        $this->assertNotNull($result);
        $this->assertSame($email, (string) $result->email());
        $this->assertSame((string) $account->accountIdentifier(), (string) $result->accountIdentifier());
    }

    /**
     * 正常系: 指定したEmailを持つAccountが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailWhenNotFound(): void
    {
        $repository = $this->app->make(AccountRepositoryInterface::class);
        $result = $repository->findByEmail(new Email('notfound@example.com'));

        $this->assertNull($result);
    }

    /**
     * 正常系: 正しく新規のAccountを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewAccount(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $email = StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $account = $this->createTestAccount(accountId: $accountId, email: $email);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $this->assertDatabaseHas('accounts', [
            'id' => $accountId,
            'email' => $email,
            'type' => 'corporation',
            'name' => 'Test Account',
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: 正しくAccountを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        // 削除前に存在確認
        $this->assertNotNull($repository->findById(new AccountIdentifier($accountId)));

        // 削除
        $repository->delete($account);

        // 削除後の確認
        $this->assertNull($repository->findById(new AccountIdentifier($accountId)));
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
    }

    /**
     * 正常系: ContractInfoが正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testContractInfoPersistence(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $contractInfo = $result->contractInfo();

        // BillingAddress
        $this->assertSame(CountryCode::JAPAN, $contractInfo->billingAddress()->countryCode());
        $this->assertSame('123-4567', (string) $contractInfo->billingAddress()->postalCode());
        $this->assertSame('Tokyo', (string) $contractInfo->billingAddress()->stateOrProvince());
        $this->assertSame('Shibuya', (string) $contractInfo->billingAddress()->city());
        $this->assertSame('1-2-3 Shibuya', (string) $contractInfo->billingAddress()->addressLine1());
        $this->assertSame('Building A', (string) $contractInfo->billingAddress()->addressLine2());
        $this->assertNull($contractInfo->billingAddress()->addressLine3());

        // BillingContact
        $this->assertSame('Test Contact', (string) $contractInfo->billingContact()->name());
        $this->assertSame('+819012345678', (string) $contractInfo->billingContact()->phone());

        // BillingMethod
        $this->assertSame(BillingMethod::CREDIT_CARD, $contractInfo->billingMethod());

        // Plan
        $this->assertSame('Standard Plan', (string) $contractInfo->plan()->planName());
        $this->assertSame(BillingCycle::MONTHLY, $contractInfo->plan()->billingCycle());
        $this->assertSame('Standard monthly plan', (string) $contractInfo->plan()->planDescription());
        $this->assertSame(1000, $contractInfo->plan()->money()->amount());
        $this->assertSame(Currency::JPY, $contractInfo->plan()->money()->currency());

        // TaxInfo
        $this->assertSame(TaxRegion::JP, $contractInfo->taxInfo()->region());
        $this->assertSame(TaxCategory::TAXABLE, $contractInfo->taxInfo()->category());
        $this->assertSame('TAX001', $contractInfo->taxInfo()->taxCode());

        // BillingStartDate
        $this->assertNotNull($contractInfo->billingStartDate());
        $this->assertSame('2024-01-01', $contractInfo->billingStartDate()->format('Y-m-d'));
    }

    /**
     * 正常系: billingStartDateがnullの場合も正しく保存・取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testContractInfoPersistenceWithNullBillingStartDate(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $account = $this->createTestAccount(accountId: $accountId, billingStartDate: null);

        $repository = $this->app->make(AccountRepositoryInterface::class);
        $repository->save($account);

        $result = $repository->findById(new AccountIdentifier($accountId));

        $this->assertNotNull($result);
        $this->assertNull($result->contractInfo()->billingStartDate());
    }
}
