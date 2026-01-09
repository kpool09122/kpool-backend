<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\DeleteIdentityGroup;

use Source\Account\Application\Exception\CannotDeleteDefaultIdentityGroupException;
use Source\Account\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;

readonly class DeleteIdentityGroup implements DeleteIdentityGroupInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
    ) {
    }

    /**
     * @throws IdentityGroupNotFoundException
     * @throws CannotDeleteDefaultIdentityGroupException
     * @throws CannotDeleteLastOwnerGroupException
     */
    public function process(DeleteIdentityGroupInputPort $input): void
    {
        $identityGroup = $this->identityGroupRepository->findById($input->identityGroupIdentifier());

        if ($identityGroup === null) {
            throw new IdentityGroupNotFoundException();
        }

        if ($identityGroup->isDefault()) {
            throw new CannotDeleteDefaultIdentityGroupException();
        }

        if ($identityGroup->role() === AccountRole::OWNER && $identityGroup->memberCount() > 0) {
            $allIdentityGroups = $this->identityGroupRepository->findByAccountId($identityGroup->accountIdentifier());

            $ownerMemberCount = 0;
            foreach ($allIdentityGroups as $identityGroupInAccount) {
                if ($identityGroupInAccount->role() === AccountRole::OWNER) {
                    $ownerMemberCount += $identityGroupInAccount->memberCount();
                }
            }

            if ($ownerMemberCount <= $identityGroup->memberCount()) {
                throw new CannotDeleteLastOwnerGroupException();
            }
        }

        $this->identityGroupRepository->delete($identityGroup);
    }
}
