<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;

readonly class CreateIdentityGroup implements CreateIdentityGroupInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private IdentityGroupFactoryInterface $identityGroupFactory,
    ) {
    }

    public function process(CreateIdentityGroupInputPort $input): IdentityGroup
    {
        $identityGroup = $this->identityGroupFactory->create(
            $input->accountIdentifier(),
            $input->name(),
            $input->role(),
            false,
        );

        $this->identityGroupRepository->save($identityGroup);

        return $identityGroup;
    }
}
