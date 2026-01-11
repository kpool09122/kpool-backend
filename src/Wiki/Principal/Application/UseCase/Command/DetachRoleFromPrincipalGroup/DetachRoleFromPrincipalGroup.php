<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class DetachRoleFromPrincipalGroup implements DetachRoleFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(DetachRoleFromPrincipalGroupInputPort $input): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $principalGroup->removeRole($input->roleIdentifier());

        $this->principalGroupRepository->save($principalGroup);
    }
}
