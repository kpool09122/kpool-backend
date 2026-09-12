<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use DateTimeInterface;
use LogicException;
use Source\Account\Delegation\Domain\Entity\Delegation;

class RequestDelegationOutput implements RequestDelegationOutputPort
{
    private ?Delegation $delegation = null;

    public function setDelegation(Delegation $delegation): void
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
            'rejectedAt' => $delegation->rejectedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
