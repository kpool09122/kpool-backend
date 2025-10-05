<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

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
     * @throws UnauthorizedException
     */
    public function process(SubmitGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
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
