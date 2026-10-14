<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GetNotificationSettingsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function accountType(): ?AccountType;
}
