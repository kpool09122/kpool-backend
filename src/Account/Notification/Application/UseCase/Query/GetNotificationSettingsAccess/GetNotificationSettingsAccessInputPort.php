<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query\GetNotificationSettingsAccess;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GetNotificationSettingsAccessInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;
}
