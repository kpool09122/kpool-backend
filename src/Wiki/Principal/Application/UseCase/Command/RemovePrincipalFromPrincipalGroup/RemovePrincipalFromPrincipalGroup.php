<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class RemovePrincipalFromPrincipalGroup implements RemovePrincipalFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(RemovePrincipalFromPrincipalGroupInputPort $input): PrincipalGroup
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $principalGroup->removeMember($input->principalIdentifier());

        $this->principalGroupRepository->save($principalGroup);

        return $principalGroup;
    }
}
