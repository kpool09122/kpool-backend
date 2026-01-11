<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use Source\Account\IdentityGroup\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;

readonly class RemoveIdentityFromIdentityGroup implements RemoveIdentityFromIdentityGroupInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
    ) {
    }

    /**
     * @throws IdentityGroupNotFoundException
     * @throws CannotRemoveLastOwnerException
     */
    public function process(RemoveIdentityFromIdentityGroupInputPort $input): IdentityGroup
    {
        $identityGroup = $this->identityGroupRepository->findById($input->identityGroupIdentifier());

        if ($identityGroup === null) {
            throw new IdentityGroupNotFoundException();
        }

        if ($identityGroup->role() === AccountRole::OWNER) {
            $allIdentityGroups = $this->identityGroupRepository->findByAccountId($identityGroup->accountIdentifier());

            $totalOwnerCount = 0;
            foreach ($allIdentityGroups as $identityGroupInAccount) {
                if ($identityGroupInAccount->role() === AccountRole::OWNER) {
                    $totalOwnerCount += $identityGroupInAccount->memberCount();
                }
            }

            if ($totalOwnerCount <= 1) {
                throw new CannotRemoveLastOwnerException();
            }
        }

        $identityGroup->removeMember($input->identityIdentifier());

        $this->identityGroupRepository->save($identityGroup);

        return $identityGroup;
    }
}
