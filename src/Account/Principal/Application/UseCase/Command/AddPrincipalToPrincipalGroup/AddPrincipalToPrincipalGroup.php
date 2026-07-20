<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class AddPrincipalToPrincipalGroup implements AddPrincipalToPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(AddPrincipalToPrincipalGroupInputPort $input, AddPrincipalToPrincipalGroupOutputPort $output): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $principalGroup->addMember(new Principal($input->principalIdentifier()));

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
