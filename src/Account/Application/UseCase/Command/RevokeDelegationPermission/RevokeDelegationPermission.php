<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegationPermission;

use Source\Account\Application\Exception\DelegationPermissionNotFoundException;
use Source\Account\Domain\Repository\DelegationPermissionRepositoryInterface;

readonly class RevokeDelegationPermission implements RevokeDelegationPermissionInterface
{
    public function __construct(
        private DelegationPermissionRepositoryInterface $delegationPermissionRepository,
    ) {
    }

    /**
     * @throws DelegationPermissionNotFoundException
     */
    public function process(RevokeDelegationPermissionInputPort $input): void
    {
        $delegationPermission = $this->delegationPermissionRepository->findById($input->delegationPermissionIdentifier());

        if ($delegationPermission === null) {
            throw new DelegationPermissionNotFoundException();
        }

        $this->delegationPermissionRepository->delete($delegationPermission);
    }
}
