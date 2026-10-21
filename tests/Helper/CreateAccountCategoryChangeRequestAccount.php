<?php

declare(strict_types=1);

namespace Tests\Helper;

use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestAccount;

class CreateAccountCategoryChangeRequestAccount
{
    public static function create(?string $locality = '千代田区'): AccountCategoryChangeRequestAccount
    {
        return new AccountCategoryChangeRequestAccount('account@example.com', 'corporation', 'Account', 'active', '+819012345678', 'JP', '13', '100-0001', $locality, '千代田1-1', null);
    }

    /** @return array<string, string|null> */
    public static function columns(): array
    {
        $account = self::create();

        return [
            'account_email' => $account->email,
            'account_type' => $account->type,
            'account_name' => $account->name,
            'account_status' => $account->status,
            'account_phone' => $account->phone,
            'account_address_country_code' => $account->countryCode,
            'account_address_administrative_area_code' => $account->administrativeAreaCode,
            'account_address_postal_code' => $account->postalCode,
            'account_address_locality' => $account->locality,
            'account_address_line1' => $account->addressLine1,
            'account_address_line2' => $account->addressLine2,
        ];
    }
}
