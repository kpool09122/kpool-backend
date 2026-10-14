<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

interface UpdateNotificationSettingsOutputPort
{
    /** @return array{} */
    public function toArray(): array;
}
