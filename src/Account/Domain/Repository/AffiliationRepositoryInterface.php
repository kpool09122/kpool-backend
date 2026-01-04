<?php

declare(strict_types=1);

namespace Source\Account\Domain\Repository;

use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\AffiliationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AffiliationRepositoryInterface
{
    public function save(AccountAffiliation $affiliation): void;

    public function delete(AccountAffiliation $affiliation): void;

    public function findById(AffiliationIdentifier $identifier): ?AccountAffiliation;

    public function findActiveByTalentAccount(AccountIdentifier $talentAccountIdentifier): ?AccountAffiliation;

    /**
     * @return AccountAffiliation[]
     */
    public function findByAgencyAccount(AccountIdentifier $agencyAccountIdentifier, ?AffiliationStatus $status = null): array;

    /**
     * @return AccountAffiliation[]
     */
    public function findByTalentAccount(AccountIdentifier $talentAccountIdentifier, ?AffiliationStatus $status = null): array;

    /**
     * @return AccountAffiliation[]
     */
    public function findPendingByApprover(AccountIdentifier $approverAccountIdentifier): array;

    public function existsActiveAffiliation(AccountIdentifier $agencyAccountIdentifier, AccountIdentifier $talentAccountIdentifier): bool;
}
