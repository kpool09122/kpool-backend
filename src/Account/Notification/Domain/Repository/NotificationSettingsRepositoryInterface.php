<?php

declare(strict_types=1);

namespace Source\Account\Notification\Domain\Repository;

use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface NotificationSettingsRepositoryInterface
{
    public function findByAccountId(AccountIdentifier $accountIdentifier): NotificationSettings;

    public function save(NotificationSettings $notificationSettings): void;
}
