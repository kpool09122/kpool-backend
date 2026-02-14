<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Repository;

use Application\Models\Monetization\MonetizationAccount as MonetizationAccountEloquent;
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

class MonetizationAccountRepository implements MonetizationAccountRepositoryInterface
{
    public function findById(MonetizationAccountIdentifier $identifier): ?MonetizationAccount
    {
        $eloquent = MonetizationAccountEloquent::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByAccountIdentifier(AccountIdentifier $accountIdentifier): ?MonetizationAccount
    {
        $eloquent = MonetizationAccountEloquent::query()
            ->where('account_id', (string) $accountIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(MonetizationAccount $monetizationAccount): void
    {
        $billingAddress = $monetizationAccount->billingAddress();
        $billingContact = $monetizationAccount->billingContact();
        $taxInfo = $monetizationAccount->taxInfo();

        MonetizationAccountEloquent::query()->updateOrCreate(
            ['id' => (string) $monetizationAccount->monetizationAccountIdentifier()],
            [
                'account_id' => (string) $monetizationAccount->accountIdentifier(),
                'capabilities' => json_encode(array_map(
                    static fn (Capability $c) => $c->value,
                    $monetizationAccount->capabilities()
                ), JSON_THROW_ON_ERROR),
                'stripe_customer_id' => $monetizationAccount->stripeCustomerId() !== null
                    ? (string) $monetizationAccount->stripeCustomerId()
                    : null,
                'stripe_connected_account_id' => $monetizationAccount->stripeConnectedAccountId() !== null
                    ? (string) $monetizationAccount->stripeConnectedAccountId()
                    : null,
                'billing_address' => $billingAddress !== null ? [
                    'country_code' => $billingAddress->countryCode()->value,
                    'postal_code' => (string) $billingAddress->postalCode(),
                    'state_or_province' => (string) $billingAddress->stateOrProvince(),
                    'city' => (string) $billingAddress->city(),
                    'address_line1' => (string) $billingAddress->addressLine1(),
                    'address_line2' => $billingAddress->addressLine2() !== null ? (string) $billingAddress->addressLine2() : null,
                    'address_line3' => $billingAddress->addressLine3() !== null ? (string) $billingAddress->addressLine3() : null,
                ] : null,
                'billing_contact' => $billingContact !== null ? [
                    'name' => (string) $billingContact->name(),
                    'email' => (string) $billingContact->email(),
                    'phone' => $billingContact->phone() !== null ? (string) $billingContact->phone() : null,
                ] : null,
                'billing_method' => $monetizationAccount->billingMethod()?->value,
                'tax_info' => $taxInfo !== null ? [
                    'region' => $taxInfo->region()->value,
                    'category' => $taxInfo->category()->value,
                    'tax_code' => $taxInfo->taxCode(),
                ] : null,
            ]
        );
    }

    private function toDomainEntity(MonetizationAccountEloquent $eloquent): MonetizationAccount
    {
        $capabilities = array_map(
            static fn (string $value) => Capability::from($value),
            json_decode($eloquent->capabilities, true, 512, JSON_THROW_ON_ERROR) ?? []
        );

        $billingAddressData = $eloquent->billing_address;
        $billingContactData = $eloquent->billing_contact;
        $taxInfoData = $eloquent->tax_info;

        return new MonetizationAccount(
            new MonetizationAccountIdentifier($eloquent->id),
            new AccountIdentifier($eloquent->account_id),
            $capabilities,
            $eloquent->stripe_customer_id !== null
                ? new PaymentCustomerId($eloquent->stripe_customer_id)
                : null,
            $eloquent->stripe_connected_account_id !== null
                ? new ConnectedAccountId($eloquent->stripe_connected_account_id)
                : null,
            $billingAddressData !== null ? new BillingAddress(
                CountryCode::from($billingAddressData['country_code']),
                new PostalCode($billingAddressData['postal_code']),
                new StateOrProvince($billingAddressData['state_or_province']),
                new City($billingAddressData['city']),
                new AddressLine($billingAddressData['address_line1']),
                isset($billingAddressData['address_line2']) ? new AddressLine($billingAddressData['address_line2']) : null,
                isset($billingAddressData['address_line3']) ? new AddressLine($billingAddressData['address_line3']) : null,
            ) : null,
            $billingContactData !== null ? new BillingContact(
                new ContractName($billingContactData['name']),
                new Email($billingContactData['email']),
                isset($billingContactData['phone']) ? new Phone($billingContactData['phone']) : null,
            ) : null,
            $eloquent->billing_method !== null
                ? BillingMethod::from($eloquent->billing_method)
                : null,
            $taxInfoData !== null ? new TaxInfo(
                TaxRegion::from($taxInfoData['region']),
                TaxCategory::from($taxInfoData['category']),
                $taxInfoData['tax_code'] ?? null,
            ) : null,
        );
    }
}
