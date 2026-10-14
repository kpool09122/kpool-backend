<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

class UpdateNotificationSettingsOutput implements UpdateNotificationSettingsOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
