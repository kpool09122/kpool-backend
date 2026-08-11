<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountCategoryChangeRequestReadModel
{
    /** @param array{code: string, detail: ?string}|null $rejectionReason */
    public function __construct(
        private string $requestIdentifier,
        private string $accountIdentifier,
        private string $currentAccountCategory,
        private string $requestedAccountCategory,
        private string $status,
        private string $requestedAt,
        private ?string $reviewedBy,
        private ?string $reviewedAt,
        private ?array $rejectionReason,
    ) {
    }

    /**
     * @return array{
     *     requestIdentifier: string,
     *     accountIdentifier: string,
     *     currentAccountCategory: string,
     *     requestedAccountCategory: string,
     *     status: string,
     *     requestedAt: string,
     *     reviewedBy: ?string,
     *     reviewedAt: ?string,
     *     rejectionReason: array{code: string, detail: ?string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'requestIdentifier' => $this->requestIdentifier,
            'accountIdentifier' => $this->accountIdentifier,
            'currentAccountCategory' => $this->currentAccountCategory,
            'requestedAccountCategory' => $this->requestedAccountCategory,
            'status' => $this->status,
            'requestedAt' => $this->requestedAt,
            'reviewedBy' => $this->reviewedBy,
            'reviewedAt' => $this->reviewedAt,
            'rejectionReason' => $this->rejectionReason,
        ];
    }
}
