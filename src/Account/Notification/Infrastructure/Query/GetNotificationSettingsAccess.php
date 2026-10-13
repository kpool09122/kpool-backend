<?php

declare(strict_types=1);

namespace Source\Account\Notification\Infrastructure\Query;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\Service\NotificationSettingsAuthorization;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess\GetNotificationSettingsAccessInputPort;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess\GetNotificationSettingsAccessInterface;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsAccessReadModel;

readonly class GetNotificationSettingsAccess implements GetNotificationSettingsAccessInterface
{
    public function __construct(private NotificationSettingsAuthorization $notificationSettingsAuthorization)
    {
    }

    /** @throws AccountUpdateForbiddenException */
    public function process(GetNotificationSettingsAccessInputPort $input): NotificationSettingsAccessReadModel
    {
        if ((string) $input->accountIdentifier() !== (string) $input->principal()->accountIdentifier()) {
            throw new AccountUpdateForbiddenException();
        }

        return new NotificationSettingsAccessReadModel($this->notificationSettingsAuthorization->canManage($input->accountIdentifier(), $input->principal()));
    }
}
