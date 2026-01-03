<?php

declare(strict_types=1);

namespace Source\Account\Infrastructure\Repository;

use Application\Models\Account\Account as AccountEloquent;
use Application\Models\Account\AccountMembership as AccountMembershipEloquent;
use DateTimeImmutable;
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
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

class AccountRepository implements AccountRepositoryInterface
{
    public function save(Account $account): void
    {
        // アカウント情報を保存
        AccountEloquent::query()->updateOrCreate(
            ['id' => (string) $account->accountIdentifier()],
            [
                'email' => (string) $account->email(),
                'type' => $account->type()->value,
                'name' => (string) $account->name(),
                'status' => $account->status()->value,
                'contract_info' => $this->contractInfoToArray($account->contractInfo()),
            ]
        );

        // 既存のメンバーシップを削除して再作成（更新のため）
        AccountMembershipEloquent::query()
            ->where('account_id', (string) $account->accountIdentifier())
            ->delete();

        // メンバーシップを保存
        foreach ($account->memberships() as $membership) {
            AccountMembershipEloquent::query()->create([
                'id' => (string) Uuid::v7(),
                'account_id' => (string) $account->accountIdentifier(),
                'identity_id' => (string) $membership->identityIdentifier(),
                'role' => $membership->role()->value,
            ]);
        }
    }

    public function findById(AccountIdentifier $identifier): ?Account
    {
        $eloquent = AccountEloquent::query()
            ->with('memberships')
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
            ->with('memberships')
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(Account $account): void
    {
        // Cascade deleteがあるのでaccountを削除すればメンバーシップも削除される
        AccountEloquent::query()
            ->where('id', (string) $account->accountIdentifier())
            ->delete();
    }

    private function toDomainEntity(AccountEloquent $eloquent): Account
    {
        /** @var AccountMembership[] $memberships */
        $memberships = $eloquent->memberships->map(function ($m) {
            /** @var AccountMembershipEloquent $m */
            return new AccountMembership(
                new IdentityIdentifier($m->identity_id),
                AccountRole::from($m->role),
            );
        })->all();

        return new Account(
            new AccountIdentifier($eloquent->id),
            new Email($eloquent->email),
            AccountType::from($eloquent->type),
            new AccountName($eloquent->name),
            $this->arrayToContractInfo($eloquent->contract_info),
            AccountStatus::from($eloquent->status),
            $memberships,
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
