<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\Service;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class NotificationSettingsAuthorization
{
    public function __construct(private PrincipalGroupRepositoryInterface $principalGroupRepository)
    {
    }

    public function assertAllowed(AccountIdentifier $accountIdentifier, Principal $principal): void
    {
        if (! $this->canManage($accountIdentifier, $principal)) {
            throw new AccountUpdateForbiddenException();
        }
    }

    public function canManage(AccountIdentifier $accountIdentifier, Principal $principal): bool
    {
        if ((string) $accountIdentifier !== (string) $principal->accountIdentifier()) {
            return false;
        }
        foreach ($this->principalGroupRepository->findByAccountIdAndPrincipal($accountIdentifier, $principal->principalIdentifier()) as $group) {
            if ($group->name() === 'Owners' && $group->delegationIdentifier() === null && $group->hasMember($principal->principalIdentifier())) {
                return true;
            }
        }

        return false;
    }
}
