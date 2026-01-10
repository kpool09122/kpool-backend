<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole;

use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class AttachPolicyToRole implements AttachPolicyToRoleInterface
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /**
     * @throws RoleNotFoundException
     * @throws PolicyNotFoundException
     */
    public function process(AttachPolicyToRoleInputPort $input): void
    {
        $role = $this->roleRepository->findById($input->roleIdentifier());

        if ($role === null) {
            throw new RoleNotFoundException();
        }

        $policy = $this->policyRepository->findById($input->policyIdentifier());

        if ($policy === null) {
            throw new PolicyNotFoundException();
        }

        $role->addPolicy($input->policyIdentifier());

        $this->roleRepository->save($role);
    }
}
