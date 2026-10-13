<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Service;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Source\Shared\Application\Service\AccountNotificationRecipient;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

class AccountNotificationRecipientService implements AccountNotificationRecipientServiceInterface
{
    /** @param AccountIdentifier[] $accountIdentifiers
     * @return list<AccountNotificationRecipient>
     */
    public function resolve(array $accountIdentifiers): array
    {
        if ($accountIdentifiers === []) {
            return [];
        }
        $accounts = array_map(static fn (AccountIdentifier $id): string => (string) $id, $accountIdentifiers);
        /** @var Collection<int, object{id: string, email: string, language: string}> $rows */
        $rows = DB::table('account_notification_recipients as recipients')
            ->join('account_principals as principals', function (JoinClause $join): void {
                $join->on('principals.account_id', '=', 'recipients.account_id')->on('principals.identity_id', '=', 'recipients.identity_id');
            })
            ->join('identities', 'identities.id', '=', 'recipients.identity_id')
            ->whereIn('recipients.account_id', $accounts)
            ->select('identities.id', 'identities.email', 'identities.language')->distinct()->orderBy('identities.id')->get();

        return array_values($rows->map(static fn (object $row): AccountNotificationRecipient => new AccountNotificationRecipient(new Email($row->email), Language::from($row->language)))->all());
    }
}
