<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Application\Service\AffiliationQueryServiceInterface;

/**
 * Anti-Corruption Layer 実装: Account ドメインの AffiliationRepository を使用して情報を取得.
 */
readonly class AffiliationQueryService implements AffiliationQueryServiceInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function findAccountIdentifiersByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?array
    {
        $affiliation = $this->affiliationRepository->findById($affiliationIdentifier);

        if ($affiliation === null) {
            return null;
        }

        return [
            'agencyAccountIdentifier' => $affiliation->agencyAccountIdentifier(),
            'talentAccountIdentifier' => $affiliation->talentAccountIdentifier(),
        ];
    }
}
