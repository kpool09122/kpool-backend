<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Service\PrincipalGroupChangeGuardInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;

readonly class RemovePrincipalFromPrincipalGroup implements RemovePrincipalFromPrincipalGroupInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupChangeGuardInterface $principalGroupChangeGuard,
    ) {
    }

    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(RemovePrincipalFromPrincipalGroupInputPort $input, RemovePrincipalFromPrincipalGroupOutputPort $output): void
    {
        $principalGroup = $this->principalGroupRepository->findById($input->principalGroupIdentifier());

        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException();
        }

        $principalGroup->removeMember($input->principalIdentifier());

        $groups = $this->principalGroupRepository->findByAccountId($principalGroup->accountIdentifier());
        foreach ($groups as $index => $group) {
            if ((string) $group->principalGroupIdentifier() === (string) $principalGroup->principalGroupIdentifier()) {
                $groups[$index] = $principalGroup;
            }
        }
        $this->principalGroupChangeGuard->assertPreserved($principalGroup->accountIdentifier(), $groups);

        $this->principalGroupRepository->save($principalGroup);

        $output->setPrincipalGroup($principalGroup);
    }
}
