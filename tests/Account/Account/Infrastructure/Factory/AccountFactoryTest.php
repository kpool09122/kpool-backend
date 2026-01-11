<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AddressLine;
use Source\Account\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Account\Domain\ValueObject\BillingContact;
use Source\Account\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Account\Domain\ValueObject\City;
use Source\Account\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Account\Domain\ValueObject\ContractName;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Account\Domain\ValueObject\Phone;
use Source\Account\Account\Domain\ValueObject\Plan;
use Source\Account\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Account\Domain\ValueObject\PlanName;
use Source\Account\Account\Domain\ValueObject\PostalCode;
use Source\Account\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Account\Domain\ValueObject\TaxRegion;
use Source\Account\Account\Infrastructure\Factory\AccountFactory;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
use Tests\TestCase;

class AccountFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(AccountFactoryInterface::class);
        $this->assertInstanceOf(AccountFactory::class, $factory);
    }

    /**
     * 正常系: 正しくAccountエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
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

        $factory = $this->app->make(AccountFactoryInterface::class);
        $account = $factory->create(
            $email,
            $accountType,
            $accountName,
            $contractInfo,
        );

        $this->assertTrue(UuidValidator::isValid((string) $account->accountIdentifier()));
        $this->assertSame($email, $account->email());
        $this->assertSame($accountType, $account->type());
        $this->assertSame($accountName, $account->name());
        $this->assertSame($contractInfo, $account->contractInfo());
        $this->assertSame(AccountCategory::GENERAL, $account->accountCategory());
        $this->assertEquals(DeletionReadinessChecklist::ready(), $account->deletionReadiness());
    }
}
