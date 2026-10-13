<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Query;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\Service\NotificationSettingsAuthorization;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInputPort;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsReadModel;
use Source\Account\Notification\Domain\Entity\NotificationSettings;

readonly class GetNotificationSettings implements GetNotificationSettingsInterface
{
    public function __construct(private NotificationSettingsAuthorization $notificationSettingsAuthorization)
    {
    }

    /** @throws AccountUpdateForbiddenException */
    public function process(GetNotificationSettingsInputPort $input): NotificationSettingsReadModel
    {
        $this->notificationSettingsAuthorization->assertAllowed($input->accountIdentifier(), $input->principal());
        /** @var Collection<int, object{id: string, identity_name: string, email: string, language: string, selected_identity_id: string|null}> $rows */
        $rows = DB::table('account_principals as principals')
            ->join('identities', 'identities.id', '=', 'principals.identity_id')
            ->leftJoin('account_notification_recipients as recipients', function (JoinClause $join): void {
                $join->on('recipients.account_id', '=', 'principals.account_id')->on('recipients.identity_id', '=', 'principals.identity_id');
            })
            ->where('principals.account_id', (string) $input->accountIdentifier())
            ->select('identities.id', 'identities.identity_name', 'identities.email', 'identities.language', 'recipients.identity_id as selected_identity_id')
            ->distinct()->orderBy('identities.id')->get();
        $candidates = [];
        $recipients = [];
        foreach ($rows as $row) {
            $candidates[] = ['identityIdentifier' => $row->id, 'name' => $row->identity_name, 'email' => $row->email, 'language' => $row->language];
            if ($row->selected_identity_id !== null) {
                $recipients[] = ['identityIdentifier' => $row->id, 'channel' => 'email'];
            }
        }

        return new NotificationSettingsReadModel($recipients, $candidates, min(count($candidates), NotificationSettings::MAX_RECIPIENTS));
    }
}
