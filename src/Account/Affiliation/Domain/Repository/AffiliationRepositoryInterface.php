<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Repository;

use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AffiliationRepositoryInterface
{
    public function save(Affiliation $affiliation): void;

    public function delete(Affiliation $affiliation): void;

    public function findById(AffiliationIdentifier $identifier): ?Affiliation;

    public function findActiveByTalentAccount(AccountIdentifier $talentAccountIdentifier): ?Affiliation;

    /**
     * @return Affiliation[]
     */
    public function findByAgencyAccount(AccountIdentifier $agencyAccountIdentifier, ?AffiliationStatus $status = null): array;

    /**
     * @return Affiliation[]
     */
    public function findByTalentAccount(AccountIdentifier $talentAccountIdentifier, ?AffiliationStatus $status = null): array;

    /**
     * @return Affiliation[]
     */
    public function findPendingByApprover(AccountIdentifier $approverAccountIdentifier): array;

    public function existsActiveAffiliation(AccountIdentifier $agencyAccountIdentifier, AccountIdentifier $talentAccountIdentifier): bool;
}
