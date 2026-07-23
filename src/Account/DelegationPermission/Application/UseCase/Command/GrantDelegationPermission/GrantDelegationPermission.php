<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\DelegationPermission\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class GrantDelegationPermission implements GrantDelegationPermissionInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private DelegationPermissionRepositoryInterface $delegationPermissionRepository,
        private DelegationPermissionFactoryInterface $delegationPermissionFactory,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(GrantDelegationPermissionInputPort $input, GrantDelegationPermissionOutputPort $output): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $delegationPermission = $this->delegationPermissionFactory->create(
            $input->principalGroupIdentifier(),
            $input->targetAccountIdentifier(),
            $input->affiliationIdentifier(),
        );

        $this->delegationPermissionRepository->save($delegationPermission);

        $output->setDelegationPermission($delegationPermission);
    }
}
