<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class CreatePrincipalGroup implements CreatePrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
    ) {
    }

    public function process(CreatePrincipalGroupInputPort $input): PrincipalGroup
    {
        $principalGroup = $this->principalGroupFactory->create(
            $input->accountIdentifier(),
            $input->name(),
            false,
        );

        $this->principalGroupRepository->save($principalGroup);

        return $principalGroup;
    }
}
