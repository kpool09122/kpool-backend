<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class GetNotificationSettingsInput implements GetNotificationSettingsInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private ?AccountType $accountType = null,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
