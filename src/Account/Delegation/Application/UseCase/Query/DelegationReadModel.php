<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Query;

readonly class DelegationReadModel
{
    /**
     * @param array{accountIdentifier: string, name: string, email: string} $delegateAccount
     * @param array{accountIdentifier: string, name: string, email: string} $delegatorAccount
     * @param array{accountIdentifier: string, name: string, email: string} $requestedByAccount
     */
    public function __construct(
        private string $delegationIdentifier,
        private string $affiliationIdentifier,
        private string $delegateAccountIdentifier,
        private string $delegatorAccountIdentifier,
        private string $requestedByAccountIdentifier,
        private array $delegateAccount,
        private array $delegatorAccount,
        private array $requestedByAccount,
        private string $status,
        private string $direction,
        private string $requestedAt,
        private ?string $approvedAt,
        private ?string $rejectedAt,
    ) {
    }

    /**
     * @return array{
     *     delegationIdentifier: string,
     *     affiliationIdentifier: string,
     *     delegateAccountIdentifier: string,
     *     delegatorAccountIdentifier: string,
     *     requestedByAccountIdentifier: string,
     *     delegateAccount: array{accountIdentifier: string, name: string, email: string},
     *     delegatorAccount: array{accountIdentifier: string, name: string, email: string},
     *     requestedByAccount: array{accountIdentifier: string, name: string, email: string},
     *     status: string,
     *     direction: string,
     *     requestedAt: string,
     *     approvedAt: string|null,
     *     rejectedAt: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'delegationIdentifier' => $this->delegationIdentifier,
            'affiliationIdentifier' => $this->affiliationIdentifier,
            'delegateAccountIdentifier' => $this->delegateAccountIdentifier,
            'delegatorAccountIdentifier' => $this->delegatorAccountIdentifier,
            'requestedByAccountIdentifier' => $this->requestedByAccountIdentifier,
            'delegateAccount' => $this->delegateAccount,
            'delegatorAccount' => $this->delegatorAccount,
            'requestedByAccount' => $this->requestedByAccount,
            'status' => $this->status,
            'direction' => $this->direction,
            'requestedAt' => $this->requestedAt,
            'approvedAt' => $this->approvedAt,
            'rejectedAt' => $this->rejectedAt,
        ];
    }
}
