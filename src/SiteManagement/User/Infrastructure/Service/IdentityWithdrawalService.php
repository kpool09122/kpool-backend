<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Application\Service\IdentityWithdrawalServiceInterface;

readonly class IdentityWithdrawalService implements IdentityWithdrawalServiceInterface
{
    public function withdraw(IdentityIdentifier $identityIdentifier): void
    {
        DB::table('contacts')->where('identity_identifier', (string) $identityIdentifier)->delete();
        DB::table('contact_replies')->where('identity_identifier', (string) $identityIdentifier)->delete();
        DB::table('site_management_users')->where('identity_id', (string) $identityIdentifier)->delete();
    }
}
