<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\IdentityWithdrawalServiceInterface;

readonly class IdentityWithdrawalService implements IdentityWithdrawalServiceInterface
{
    public function withdraw(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void
    {
        $principals = DB::table('wiki_principals')->where('identity_id', (string) $identityIdentifier)->get(['id', 'account_id']);
        foreach ($principals as $principal) {
            DB::table('archived_principals')->insert([
                'identity_id' => (string) $identityIdentifier,
                'principal_type' => 'wiki',
                'principal_id' => $principal->id,
                'account_id' => $principal->account_id,
                'archived_at' => $archivedAt,
            ]);
        }
        DB::table('wiki_principals')->where('identity_id', (string) $identityIdentifier)->delete();
        DB::afterCommit(static fn () => app(AuthContextCache::class)->forgetWiki($identityIdentifier));
    }

    public function deleteAccountData(AccountIdentifier $accountIdentifier): void
    {
        DB::table('wiki_roles')->where('account_id', (string) $accountIdentifier)->delete();
        DB::table('wiki_policies')->where('account_id', (string) $accountIdentifier)->delete();
        DB::table('official_certifications')->where('owner_account_id', (string) $accountIdentifier)->delete();
        DB::table('wikis')->where('owner_account_id', (string) $accountIdentifier)->update(['owner_account_id' => null]);
    }
}
