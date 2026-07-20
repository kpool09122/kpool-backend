<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Account\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Account\Principal\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;

readonly class DeletePrincipalGroup implements DeletePrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotDeleteDefaultPrincipalGroupException
     * @throws CannotDeleteLastOwnerGroupException
     */
    public function process(DeletePrincipalGroupInputPort $input): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        if ($principalGroup->isDefault()) {
            throw new CannotDeleteDefaultPrincipalGroupException();
        }

        if ($principalGroup->role() === AccountRole::OWNER && $principalGroup->memberCount() > 0) {
            $allPrincipalGroups = $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());

            $ownerMemberCount = 0;
            foreach ($allPrincipalGroups as $principalGroupInAccount) {
                if ($principalGroupInAccount->role() === AccountRole::OWNER) {
                    $ownerMemberCount += $principalGroupInAccount->memberCount();
                }
            }

            if ($ownerMemberCount <= $principalGroup->memberCount()) {
                throw new CannotDeleteLastOwnerGroupException();
            }
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
