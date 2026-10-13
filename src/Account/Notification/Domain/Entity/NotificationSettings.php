<?php

declare(strict_types=1);

namespace Source\Account\Notification\Domain\Entity;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class NotificationSettings
{
    public const int MAX_RECIPIENTS = 5;

    /** @param IdentityIdentifier[] $identityIdentifiers */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private array $identityIdentifiers,
    ) {
        if (count($identityIdentifiers) > self::MAX_RECIPIENTS) {
            throw new InvalidArgumentException('At most five notification recipients are allowed.');
        }
        $ids = array_map(static fn (IdentityIdentifier $id): string => (string) $id, $identityIdentifiers);
        if (count(array_unique($ids)) !== count($ids)) {
            throw new InvalidArgumentException('Notification recipients must be unique.');
        }
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    /** @return IdentityIdentifier[] */
    public function identityIdentifiers(): array
    {
        return $this->identityIdentifiers;
    }
}
