<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;

readonly class GrantDelegationPermission implements GrantDelegationPermissionInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private DelegationPermissionRepositoryInterface $delegationPermissionRepository,
        private DelegationPermissionFactoryInterface $delegationPermissionFactory,
    ) {
    }

    /**
     * @throws IdentityGroupNotFoundException
     */
    public function process(GrantDelegationPermissionInputPort $input): DelegationPermission
    {
        $identityGroup = $this->identityGroupRepository->findById($input->identityGroupIdentifier());

        if ($identityGroup === null) {
            throw new IdentityGroupNotFoundException();
        }

        $delegationPermission = $this->delegationPermissionFactory->create(
            $input->identityGroupIdentifier(),
            $input->targetAccountIdentifier(),
            $input->affiliationIdentifier(),
        );

        $this->delegationPermissionRepository->save($delegationPermission);

        return $delegationPermission;
    }
}
