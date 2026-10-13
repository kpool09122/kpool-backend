<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsReadModel;

interface GetNotificationSettingsInterface
{
    /** @throws AccountUpdateForbiddenException */
    public function process(GetNotificationSettingsInputPort $input): NotificationSettingsReadModel;
}
