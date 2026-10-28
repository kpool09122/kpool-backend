<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountNotificationRecipientServiceInterface
{
    /**
     * Resolve current account members, deduplicating a batch by Identity.
     * @param AccountIdentifier[] $accountIdentifiers
     * @return list<AccountNotificationRecipient>
     */
    public function resolve(array $accountIdentifiers): array;

    /**
     * @param AccountIdentifier[] $accountIdentifiers
     * @return array<string, AccountNotificationRecipient> Indexed by Identity identifier.
     * @phpstan-impure
     */
    public function resolveActive(array $accountIdentifiers): array;
}
