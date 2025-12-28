<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Exception\AlreadyUserExistsException;
use Source\SiteManagement\User\Domain\Factory\UserFactoryInterface;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class ProvisionUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface    $userFactory,
    ) {
    }

    /**
     * @param ProvisionUserInputPort $inputPort
     * @return User
     * @throws AlreadyUserExistsException
     */
    public function process(ProvisionUserInputPort $inputPort): User
    {
        $existingUser = $this->userRepository->findByIdentityIdentifier($inputPort->identityIdentifier());

        if ($existingUser !== null) {
            throw new AlreadyUserExistsException();
        }

        $user = $this->userFactory->create($inputPort->identityIdentifier());
        $this->userRepository->save($user);

        return $user;
    }
}
