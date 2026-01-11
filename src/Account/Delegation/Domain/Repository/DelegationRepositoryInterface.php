<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Repository;

use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DelegationRepositoryInterface
{
    public function save(Delegation $delegation): void;

    public function delete(Delegation $delegation): void;

    public function findById(DelegationIdentifier $identifier): ?Delegation;

    /**
     * @return Delegation[]
     */
    public function findByAffiliation(AffiliationIdentifier $affiliationIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return Delegation[]
     */
    public function findByDelegate(IdentityIdentifier $delegateIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return Delegation[]
     */
    public function findByDelegator(IdentityIdentifier $delegatorIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return Delegation[]
     */
    public function findApprovedByAffiliation(AffiliationIdentifier $affiliationIdentifier): array;
}
