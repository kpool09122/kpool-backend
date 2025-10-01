<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RejectGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class RejectGroup implements RejectGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    /**
     * @param RejectGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $group->setStatus(ApprovalStatus::Rejected);

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
