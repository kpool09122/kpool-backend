<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\SiteManagement\User\Domain\Exception\AlreadyUserExistsException;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class ProvisionUser implements ProvisionUserInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface    $userFactory,
    ) {
    }

    /**
     * @param ProvisionUserInputPort $inputPort
     * @throws AlreadyUserExistsException
     */
    public function process(ProvisionUserInputPort $inputPort, ProvisionUserOutputPort $output): void
    {
        $existingUser = $this->userRepository->findByIdentityIdentifier($inputPort->identityIdentifier());

        if ($existingUser !== null) {
            throw new AlreadyUserExistsException();
        }

        $user = $this->userFactory->create($inputPort->identityIdentifier());
        $this->userRepository->save($user);

        $output->setUser($user);
    }
}
