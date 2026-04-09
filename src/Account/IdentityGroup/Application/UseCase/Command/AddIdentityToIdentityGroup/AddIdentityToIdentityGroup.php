<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup;

use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;

readonly class AddIdentityToIdentityGroup implements AddIdentityToIdentityGroupInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
    ) {
    }

    /**
     * @throws IdentityGroupNotFoundException
     */
    public function process(AddIdentityToIdentityGroupInputPort $input, AddIdentityToIdentityGroupOutputPort $output): void
    {
        $identityGroup = $this->identityGroupRepository->findById($input->identityGroupIdentifier());

        if ($identityGroup === null) {
            throw new IdentityGroupNotFoundException();
        }

        $identityGroup->addMember($input->identityIdentifier());

        $this->identityGroupRepository->save($identityGroup);

        $output->setIdentityGroup($identityGroup);
    }
}
