<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsAccessReadModel;

interface GetNotificationSettingsAccessInterface
{
    /** @throws AccountUpdateForbiddenException */
    public function process(GetNotificationSettingsAccessInputPort $input): NotificationSettingsAccessReadModel;
}
