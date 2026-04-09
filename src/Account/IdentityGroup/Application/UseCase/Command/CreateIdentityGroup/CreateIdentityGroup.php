<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;

readonly class CreateIdentityGroup implements CreateIdentityGroupInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private IdentityGroupFactoryInterface $identityGroupFactory,
    ) {
    }

    public function process(CreateIdentityGroupInputPort $input, CreateIdentityGroupOutputPort $output): void
    {
        $identityGroup = $this->identityGroupFactory->create(
            $input->accountIdentifier(),
            $input->name(),
            $input->role(),
            false,
        );

        $this->identityGroupRepository->save($identityGroup);

        $output->setIdentityGroup($identityGroup);
    }
}
