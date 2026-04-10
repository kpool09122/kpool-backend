<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use DateTimeInterface;
use Source\Account\Delegation\Domain\Entity\Delegation;

class ApproveDelegationOutput implements ApproveDelegationOutputPort
{
    private ?Delegation $delegation = null;

    public function setDelegation(Delegation $delegation): void
    {
        $this->delegation = $delegation;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->delegation === null) {
            return [];
        }

        $delegation = $this->delegation;

        return [
            'delegationIdentifier' => (string) $delegation->delegationIdentifier(),
            'affiliationIdentifier' => (string) $delegation->affiliationIdentifier(),
            'delegateIdentifier' => (string) $delegation->delegateIdentifier(),
            'delegatorIdentifier' => (string) $delegation->delegatorIdentifier(),
            'status' => $delegation->status()->value,
            'direction' => $delegation->direction()->value,
            'requestedAt' => $delegation->requestedAt()->format(DateTimeInterface::ATOM),
            'approvedAt' => $delegation->approvedAt()?->format(DateTimeInterface::ATOM),
            'revokedAt' => $delegation->revokedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
