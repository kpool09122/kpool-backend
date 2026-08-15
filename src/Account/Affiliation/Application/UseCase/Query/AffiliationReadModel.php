<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Query;

readonly class AffiliationReadModel
{
    /**
     * @param array{accountIdentifier: string, name: string, email: string} $agencyAccount
     * @param array{accountIdentifier: string, name: string, email: string} $talentAccount
     * @param array{revenueSharePercentage: int|null, contractNotes: string|null}|null $terms
     */
    public function __construct(
        private string $affiliationIdentifier,
        private string $agencyAccountIdentifier,
        private string $talentAccountIdentifier,
        private array $agencyAccount,
        private array $talentAccount,
        private string $requestedBy,
        private string $status,
        private ?array $terms,
        private string $requestedAt,
        private ?string $activatedAt,
        private ?string $terminatedAt,
    ) {
    }

    /**
     * @return array{
     *     affiliationIdentifier: string,
     *     agencyAccountIdentifier: string,
     *     talentAccountIdentifier: string,
     *     agencyAccount: array{accountIdentifier: string, name: string, email: string},
     *     talentAccount: array{accountIdentifier: string, name: string, email: string},
     *     requestedBy: string,
     *     status: string,
     *     terms: array{revenueSharePercentage: int|null, contractNotes: string|null}|null,
     *     requestedAt: string,
     *     activatedAt: string|null,
     *     terminatedAt: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'affiliationIdentifier' => $this->affiliationIdentifier,
            'agencyAccountIdentifier' => $this->agencyAccountIdentifier,
            'talentAccountIdentifier' => $this->talentAccountIdentifier,
            'agencyAccount' => $this->agencyAccount,
            'talentAccount' => $this->talentAccount,
            'requestedBy' => $this->requestedBy,
            'status' => $this->status,
            'terms' => $this->terms,
            'requestedAt' => $this->requestedAt,
            'activatedAt' => $this->activatedAt,
            'terminatedAt' => $this->terminatedAt,
        ];
    }
}
