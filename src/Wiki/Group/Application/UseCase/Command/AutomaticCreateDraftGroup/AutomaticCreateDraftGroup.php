<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\AutomaticDraftGroupCreationServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

readonly class AutomaticCreateDraftGroup implements AutomaticCreateDraftGroupInterface
{
    public function __construct(
        private AutomaticDraftGroupCreationServiceInterface $automaticDraftGroupCreationService,
        private GroupRepositoryInterface $groupRepository,
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param AutomaticCreateDraftGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftGroupInputPort $input): DraftGroup
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $role = $principal->role();
        if ($role !== Role::ADMINISTRATOR && $role !== Role::SENIOR_COLLABORATOR) {
            throw new UnauthorizedException();
        }

        $draftGroup = $this->automaticDraftGroupCreationService->create($input->payload(), $principal);
        $this->groupRepository->saveDraft($draftGroup);

        return $draftGroup;
    }
}
