<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;

interface AffiliationGrantRepositoryInterface
{
    public function save(AffiliationGrant $affiliationGrant): void;

    public function findById(AffiliationGrantIdentifier $affiliationGrantIdentifier): ?AffiliationGrant;

    /**
     * @return AffiliationGrant[]
     */
    public function findByAffiliationId(AffiliationIdentifier $affiliationIdentifier): array;

    public function findByAffiliationIdAndType(
        AffiliationIdentifier $affiliationIdentifier,
        AffiliationGrantType $type,
    ): ?AffiliationGrant;

    public function delete(AffiliationGrant $affiliationGrant): void;
}
