<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class UpdateNotificationSettingsInput implements UpdateNotificationSettingsInputPort
{
    /** @param IdentityIdentifier[] $identityIdentifiers */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private array $identityIdentifiers,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    /** @return IdentityIdentifier[] */
    public function identityIdentifiers(): array
    {
        return $this->identityIdentifiers;
    }
}
