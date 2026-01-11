<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class AddPrincipalToPrincipalGroup implements AddPrincipalToPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(AddPrincipalToPrincipalGroupInputPort $input): PrincipalGroup
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $principalGroup->addMember($input->principalIdentifier());

        $this->principalGroupRepository->save($principalGroup);

        return $principalGroup;
    }
}
