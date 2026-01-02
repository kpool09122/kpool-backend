<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\DisallowedChangeRoleException;
use Source\Wiki\Principal\Domain\Exception\OperatorNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

readonly class ChangePrincipalRole implements ChangePrincipalRoleInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param ChangePrincipalRoleInputPort $input
     * @return Principal
     * @throws DisallowedChangeRoleException
     * @throws OperatorNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(ChangePrincipalRoleInputPort $input): Principal
    {
        $operator = $this->principalRepository->findById($input->operatorIdentifier());

        if ($operator === null) {
            throw new OperatorNotFoundException();
        }

        if ($operator->role() !== Role::ADMINISTRATOR) {
            throw new DisallowedChangeRoleException();
        }

        $target = $this->principalRepository->findById($input->targetPrincipalIdentifier());
        if ($target === null) {
            throw new PrincipalNotFoundException();
        }
        $target->setRole($input->targetRole());

        $this->principalRepository->save($target);

        return $target;
    }
}
