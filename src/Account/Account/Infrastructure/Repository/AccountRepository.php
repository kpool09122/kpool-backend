<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\Account as AccountEloquent;
use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
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
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;

class AccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        AccountEloquent::query()->updateOrCreate(
            ['id' => (string) $account->accountIdentifier()],
            [
                'email' => (string) $account->email(),
                'type' => $account->type()->value,
                'name' => (string) $account->name(),
                'status' => $account->status()->value,
                'category' => $account->accountCategory()->value,
                'contract_info' => $this->contractInfoToArray($account->contractInfo()),
            ]
        );
    }

    public function findById(AccountIdentifier $identifier): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByEmail(Email $email): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(Account $account): void
    {
        AccountEloquent::query()
            ->where('id', (string) $account->accountIdentifier())
            ->delete();
    }

    private function toDomainEntity(AccountEloquent $eloquent): Account
    {
        return new Account(
            new AccountIdentifier($eloquent->id),
            new Email($eloquent->email),
            AccountType::from($eloquent->type),
            new AccountName($eloquent->name),
            $this->arrayToContractInfo($eloquent->contract_info),
            AccountStatus::from($eloquent->status),
            AccountCategory::from($eloquent->category),
            DeletionReadinessChecklist::ready(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contractInfoToArray(ContractInfo $contractInfo): array
    {
        $billingAddress = $contractInfo->billingAddress();
        $billingContact = $contractInfo->billingContact();
        $plan = $contractInfo->plan();
        $taxInfo = $contractInfo->taxInfo();

        return [
            'billing_address' => [
                'country_code' => $billingAddress->countryCode()->value,
                'postal_code' => (string) $billingAddress->postalCode(),
                'state_or_province' => (string) $billingAddress->stateOrProvince(),
                'city' => (string) $billingAddress->city(),
                'address_line1' => (string) $billingAddress->addressLine1(),
                'address_line2' => $billingAddress->addressLine2() !== null ? (string) $billingAddress->addressLine2() : null,
                'address_line3' => $billingAddress->addressLine3() !== null ? (string) $billingAddress->addressLine3() : null,
            ],
            'billing_contact' => [
                'name' => (string) $billingContact->name(),
                'email' => (string) $billingContact->email(),
                'phone' => $billingContact->phone() !== null ? (string) $billingContact->phone() : null,
            ],
            'billing_method' => $contractInfo->billingMethod()->value,
            'plan' => [
                'plan_name' => (string) $plan->planName(),
                'billing_cycle' => $plan->billingCycle()->value,
                'plan_description' => (string) $plan->planDescription(),
                'money' => [
                    'amount' => $plan->money()->amount(),
                    'currency' => $plan->money()->currency()->value,
                ],
            ],
            'tax_info' => [
                'region' => $taxInfo->region()->value,
                'category' => $taxInfo->category()->value,
                'tax_code' => $taxInfo->taxCode(),
            ],
            'billing_start_date' => $contractInfo->billingStartDate()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function arrayToContractInfo(array $data): ContractInfo
    {
        $addressData = $data['billing_address'];
        $billingAddress = new BillingAddress(
            CountryCode::from($addressData['country_code']),
            new PostalCode($addressData['postal_code']),
            new StateOrProvince($addressData['state_or_province']),
            new City($addressData['city']),
            new AddressLine($addressData['address_line1']),
            isset($addressData['address_line2']) ? new AddressLine($addressData['address_line2']) : null,
            isset($addressData['address_line3']) ? new AddressLine($addressData['address_line3']) : null,
        );

        $contactData = $data['billing_contact'];
        $billingContact = new BillingContact(
            new ContractName($contactData['name']),
            new Email($contactData['email']),
            isset($contactData['phone']) ? new Phone($contactData['phone']) : null,
        );

        $planData = $data['plan'];
        $plan = new Plan(
            new PlanName($planData['plan_name']),
            BillingCycle::from($planData['billing_cycle']),
            new PlanDescription($planData['plan_description']),
            new Money($planData['money']['amount'], Currency::from($planData['money']['currency'])),
        );

        $taxData = $data['tax_info'];
        $taxInfo = new TaxInfo(
            TaxRegion::from($taxData['region']),
            TaxCategory::from($taxData['category']),
            $taxData['tax_code'] ?? null,
        );

        $billingStartDate = isset($data['billing_start_date'])
            ? new DateTimeImmutable($data['billing_start_date'])
            : null;

        return new ContractInfo(
            $billingAddress,
            $billingContact,
            BillingMethod::from($data['billing_method']),
            $plan,
            $taxInfo,
            $billingStartDate,
        );
    }
}
