<?php

declare(strict_types=1);

namespace Source\Account\Domain\Repository;

use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DelegationRepositoryInterface
{
    public function save(OperationDelegation $delegation): void;

    public function delete(OperationDelegation $delegation): void;

    public function findById(DelegationIdentifier $identifier): ?OperationDelegation;

    /**
     * @return OperationDelegation[]
     */
    public function findByAffiliation(AffiliationIdentifier $affiliationIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return OperationDelegation[]
     */
    public function findByDelegate(IdentityIdentifier $delegateIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return OperationDelegation[]
     */
    public function findByDelegator(IdentityIdentifier $delegatorIdentifier, ?DelegationStatus $status = null): array;

    /**
     * @return OperationDelegation[]
     */
    public function findApprovedByAffiliation(AffiliationIdentifier $affiliationIdentifier): array;
}
