<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class SubmitGroup implements SubmitGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    /**
     * @param SubmitGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function process(SubmitGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::Pending
        && $group->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $group->setStatus(ApprovalStatus::UnderReview);

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
