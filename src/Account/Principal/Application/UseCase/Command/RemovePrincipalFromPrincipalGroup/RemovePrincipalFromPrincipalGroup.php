<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;

readonly class RemovePrincipalFromPrincipalGroup implements RemovePrincipalFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotRemoveLastOwnerException
     */
    public function process(RemovePrincipalFromPrincipalGroupInputPort $input, RemovePrincipalFromPrincipalGroupOutputPort $output): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        if ($principalGroup->role() === AccountRole::OWNER) {
            $allPrincipalGroups = $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());

            $totalOwnerCount = 0;
            foreach ($allPrincipalGroups as $principalGroupInAccount) {
                if ($principalGroupInAccount->role() === AccountRole::OWNER) {
                    $totalOwnerCount += $principalGroupInAccount->memberCount();
                }
            }

            if ($totalOwnerCount <= 1) {
                throw new CannotRemoveLastOwnerException();
            }
        }

        $principalGroup->removeMember(new Principal($input->principalIdentifier()));

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
