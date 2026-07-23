<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class CreatePrincipalGroup implements CreatePrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
    ) {
    }

    public function process(CreatePrincipalGroupInputPort $input, CreatePrincipalGroupOutputPort $output): void
    {
        $principalGroup = $this->principalGroupFactory->create(
            $input->accountIdentifier(),
            $input->name(),
            $input->role(),
            false,
        );

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
