<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\AccessControl\Application\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\AccessControl\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\AccessControl\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface    $principalFactory,
    ) {
    }

    /**
     * @param CreatePrincipalInputPort $input
     * @return Principal
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input): Principal
    {
        $existingPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $input->identityIdentifier()
        );

        if ($existingPrincipal !== null) {
            throw new PrincipalAlreadyExistsException();
        }

        $principal = $this->principalFactory->create(
            $input->identityIdentifier(),
        );

        $this->principalRepository->save($principal);

        return $principal;
    }
}
