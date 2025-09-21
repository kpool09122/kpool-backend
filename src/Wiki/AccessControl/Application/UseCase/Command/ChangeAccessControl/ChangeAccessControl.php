<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\Exception\ActorNotFoundException;
use Source\Wiki\AccessControl\Application\Exception\UnauthorizedChangingACException;
use Source\Wiki\AccessControl\Domain\Repository\ActorRepositoryInterface;
use Source\Wiki\Shared\Domain\Entity\Actor;
use Source\Wiki\Shared\Domain\ValueObject\Role;

class ChangeAccessControl implements ChangeAccessControlInterface
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
    ) {
    }

    /**
     * @param ChangeAccessControlInputPort $input
     * @return Actor
     * @throws UnauthorizedChangingACException
     * @throws ActorNotFoundException
     */
    public function process(ChangeAccessControlInputPort $input): Actor
    {
        if ($input->holdingRole() !== Role::ADMINISTRATOR) {
            throw new UnauthorizedChangingACException();
        }

        $actor = $this->actorRepository->findById($input->actorIdentifier());

        if ($actor === null) {
            throw new ActorNotFoundException();
        }

        $actor->setRole($input->targetRole());

        $this->actorRepository->save($actor);

        return $actor;
    }
}
