<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query;

readonly class DelegationReadModel
{
    public function __construct(
        private string $delegationIdentifier,
        private string $affiliationIdentifier,
        private string $delegateAccountIdentifier,
        private string $delegatorAccountIdentifier,
        private string $requestedByAccountIdentifier,
        private string $status,
        private string $direction,
        private string $requestedAt,
        private ?string $approvedAt,
        private ?string $revokedAt,
    ) {
    }

    /** @return array{delegationIdentifier: string, affiliationIdentifier: string, delegateAccountIdentifier: string, delegatorAccountIdentifier: string, requestedByAccountIdentifier: string, status: string, direction: string, requestedAt: string, approvedAt: string|null, revokedAt: string|null} */
    public function toArray(): array
    {
        return [
            'delegationIdentifier' => $this->delegationIdentifier,
            'affiliationIdentifier' => $this->affiliationIdentifier,
            'delegateAccountIdentifier' => $this->delegateAccountIdentifier,
            'delegatorAccountIdentifier' => $this->delegatorAccountIdentifier,
            'requestedByAccountIdentifier' => $this->requestedByAccountIdentifier,
            'status' => $this->status,
            'direction' => $this->direction,
            'requestedAt' => $this->requestedAt,
            'approvedAt' => $this->approvedAt,
            'revokedAt' => $this->revokedAt,
        ];
    }
}
