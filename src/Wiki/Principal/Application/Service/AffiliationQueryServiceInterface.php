<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

/**
 * Anti-Corruption Layer: Account ドメインの Affiliation 情報を Wiki ドメインから参照するための ACL.
 */
interface AffiliationQueryServiceInterface
{
    /**
     * Affiliation に紐づく Agency と Talent の AccountIdentifier を取得する.
     *
     * @return array{agencyAccountIdentifier: AccountIdentifier, talentAccountIdentifier: AccountIdentifier}|null
     */
    public function findAccountIdentifiersByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?array;
}
