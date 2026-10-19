<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Wiki\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Service\PrincipalGroupChangeGuardInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class DeletePrincipalGroup implements DeletePrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupChangeGuardInterface $principalGroupChangeGuard,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotDeleteDefaultPrincipalGroupException
     */
    public function process(DeletePrincipalGroupInputPort $input): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        if ($principalGroup->isDefault()) {
            throw new CannotDeleteDefaultPrincipalGroupException();
        }

        $remainingGroups = array_values(array_filter(
            $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier()),
            static fn ($group): bool => (string) $group->principalGroupIdentifier() !== (string) $principalGroup->principalGroupIdentifier(),
        ));
        $this->principalGroupChangeGuard->assertPreserved($principalGroup->accountIdentifier(), $remainingGroups);
        $this->principalGroupRepository->delete($principalGroup);
    }
}
