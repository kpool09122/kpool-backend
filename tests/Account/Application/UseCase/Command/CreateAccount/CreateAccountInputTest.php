<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\CreateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
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
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;

class CreateAccountInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $email = new Email('test@test.com');
        $accountType = AccountType::INDIVIDUAL;
        $accountName = new AccountName('test-account');
        $contractInfo = $this->createContractInfo();
        $initialMembers = [
            new AccountMembership(
                new IdentityIdentifier(StrTestHelper::generateUuid()),
                AccountRole::OWNER,
            ),
        ];

        $input = new CreateAccountInput(
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $initialMembers,
        );

        $this->assertSame($email, $input->email());
        $this->assertSame($accountType, $input->accountType());
        $this->assertSame($accountName, $input->accountName());
        $this->assertSame($contractInfo, $input->contractInfo());
        $this->assertSame($initialMembers, $input->initialMembers());
    }

    private function createContractInfo(): ContractInfo
    {
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

        $taxInfo = new TaxInfo(
            region: TaxRegion::JP,
            category: TaxCategory::TAXABLE,
            taxCode: 'T1234567890',
        );

        return new ContractInfo(
            billingAddress: $address,
            billingContact: $contact,
            billingMethod: BillingMethod::INVOICE,
            plan: $plan,
            taxInfo: $taxInfo,
        );
    }
}
