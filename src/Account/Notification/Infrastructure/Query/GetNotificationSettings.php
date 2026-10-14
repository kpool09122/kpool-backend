<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Query;

use Application\Models\Identity\Identity as IdentityEloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInputPort;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsReadModel;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class GetNotificationSettings implements GetNotificationSettingsInterface
{
    public function __construct(private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    /** @throws AccountUpdateForbiddenException */
    public function process(GetNotificationSettingsInputPort $input): NotificationSettingsReadModel
    {
        if ((string) $input->accountIdentifier() !== (string) $input->principal()->accountIdentifier()
            || ! $this->policyEvaluator->evaluate($input->principal(), Action::NOTIFICATION_SETTINGS_READ, Resource::account($input->accountIdentifier(), $input->accountType()))) {
            throw new AccountUpdateForbiddenException();
        }
        $accountId = (string) $input->accountIdentifier();
        $rows = IdentityEloquent::query()
            ->whereHas('accountPrincipals', static function (Builder $query) use ($accountId): void {
                $query->where('account_id', $accountId);
            })
            ->with([
                'accountNotificationRecipients' => /** @param Relation<*, *, *> $query */
                    static function (Relation $query) use ($accountId): void {
                        $query->where('account_id', $accountId);
                    },
            ])
            ->orderBy('identities.id')->get();
        $candidates = [];
        $recipients = [];
        foreach ($rows as $row) {
            $candidates[] = ['identityIdentifier' => $row->id, 'name' => $row->identity_name, 'email' => $row->email, 'language' => $row->language];
            if ($row->accountNotificationRecipients->isNotEmpty()) {
                $recipients[] = ['identityIdentifier' => $row->id, 'channel' => 'email'];
            }
        }

        return new NotificationSettingsReadModel($recipients, $candidates, min(count($candidates), NotificationSettings::MAX_RECIPIENTS));
    }
}
