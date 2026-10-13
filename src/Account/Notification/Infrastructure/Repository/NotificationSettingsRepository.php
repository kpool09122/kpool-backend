<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Account\Notification\Domain\Repository\NotificationSettingsRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class NotificationSettingsRepository implements NotificationSettingsRepositoryInterface
{
    public function findByAccountId(AccountIdentifier $accountIdentifier): NotificationSettings
    {
        /** @var Collection<int, string> $ids */
        $ids = DB::table('account_notification_recipients')->where('account_id', (string) $accountIdentifier)->orderBy('identity_id')->pluck('identity_id');

        return new NotificationSettings($accountIdentifier, $ids->map(static fn (string $id): IdentityIdentifier => new IdentityIdentifier($id))->all());
    }

    public function save(NotificationSettings $notificationSettings): void
    {
        $account = (string) $notificationSettings->accountIdentifier();
        DB::table('account_notification_recipients')->where('account_id', $account)->delete();
        foreach ($notificationSettings->identityIdentifiers() as $identityIdentifier) {
            DB::table('account_notification_recipients')->insert(['account_id' => $account, 'identity_id' => (string) $identityIdentifier]);
        }
    }
}
