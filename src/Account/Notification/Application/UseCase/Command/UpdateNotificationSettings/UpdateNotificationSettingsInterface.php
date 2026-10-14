<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use InvalidArgumentException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;

interface UpdateNotificationSettingsInterface
{
    /** @throws AccountUpdateForbiddenException
     * @throws AccountNotFoundException
     * @throws InvalidArgumentException
     */
    public function process(UpdateNotificationSettingsInputPort $input, UpdateNotificationSettingsOutputPort $output): void;
}
