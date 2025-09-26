<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\ApproveUpdatedGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\GroupServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ApproveUpdatedGroup implements ApproveUpdatedGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private GroupServiceInterface $groupService,
    ) {
    }

    /**
     * @param ApproveUpdatedGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     */
    public function process(ApproveUpdatedGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }


        if ($input->publishedGroupIdentifier()) {
            if ($this->groupService->existsApprovedButNotTranslatedGroup(
                $input->groupIdentifier(),
                $input->publishedGroupIdentifier(),
            )) {
                throw new ExistsApprovedButNotTranslatedGroupException();
            }
        }


        $group->setStatus(ApprovalStatus::Approved);

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
