<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\Exception\ActorNotFoundException;
use Source\Wiki\AccessControl\Application\Exception\UnauthorizedChangingACException;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;

class ChangeAccessControl implements ChangeAccessControlInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param ChangeAccessControlInputPort $input
     * @return Principal
     * @throws UnauthorizedChangingACException
     * @throws ActorNotFoundException
     */
    public function process(ChangeAccessControlInputPort $input): Principal
    {
        if ($input->holdingRole() !== Role::ADMINISTRATOR) {
            throw new UnauthorizedChangingACException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());

        if ($principal === null) {
            throw new ActorNotFoundException();
        }

        $principal->setRole($input->targetRole());

        $this->principalRepository->save($principal);

        return $principal;
    }
}
