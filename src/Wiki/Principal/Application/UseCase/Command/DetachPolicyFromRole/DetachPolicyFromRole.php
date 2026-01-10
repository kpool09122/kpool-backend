<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class DetachPolicyFromRole implements DetachPolicyFromRoleInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @throws RoleNotFoundException
     */
    public function process(DetachPolicyFromRoleInputPort $input): void
    {
        $role = $this->roleRepository->findById($input->roleIdentifier());

        if ($role === null) {
            throw new RoleNotFoundException();
        }

        $role->removePolicy($input->policyIdentifier());

        $this->roleRepository->save($role);
    }
}
