<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service;

use Application\Models\Identity\Identity as IdentityEloquent;
use Illuminate\Database\Eloquent\Builder;
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
        $rows = IdentityEloquent::query()
            ->whereHas('accountPrincipals', static function (Builder $query) use ($accounts): void {
                $query->whereIn('account_id', $accounts)
                    ->whereHas('account.notificationRecipients', static function (Builder $query): void {
                        $query->whereColumn('account_notification_recipients.identity_id', 'identities.id');
                    });
            })
            ->orderBy('identities.id')->get(['identities.id', 'identities.email', 'identities.language']);

        return array_values($rows->map(static fn (IdentityEloquent $row): AccountNotificationRecipient => new AccountNotificationRecipient(new Email($row->email), Language::from($row->language)))->all());
    }
}
