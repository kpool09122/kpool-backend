<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface UpdateNotificationSettingsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    /** @return IdentityIdentifier[] */
    public function identityIdentifiers(): array;
}
