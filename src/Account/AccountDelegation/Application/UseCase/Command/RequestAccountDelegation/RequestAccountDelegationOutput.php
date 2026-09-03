<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

use DateTimeInterface;
use LogicException;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;

class RequestAccountDelegationOutput implements RequestAccountDelegationOutputPort
{
    private ?AccountDelegation $delegation = null;

    public function setDelegation(AccountDelegation $delegation): void
    {
        $this->delegation = $delegation;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $delegation = $this->delegation ?? throw new LogicException('Account delegation has not been set.');

        return [
            'delegationIdentifier' => (string) $delegation->delegationIdentifier(),
            'affiliationIdentifier' => (string) $delegation->affiliationIdentifier(),
            'delegateAccountIdentifier' => (string) $delegation->delegateAccountIdentifier(),
            'delegatorAccountIdentifier' => (string) $delegation->delegatorAccountIdentifier(),
            'requestedByAccountIdentifier' => (string) $delegation->requestedByAccountIdentifier(),
            'status' => $delegation->status()->value,
            'direction' => $delegation->direction()->value,
            'requestedAt' => $delegation->requestedAt()->format(DateTimeInterface::ATOM),
            'approvedAt' => $delegation->approvedAt()?->format(DateTimeInterface::ATOM),
            'revokedAt' => $delegation->revokedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
