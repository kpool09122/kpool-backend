<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\ApproveGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ApproveGroup implements ApproveGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private GroupServiceInterface $groupService,
    ) {
    }

    /**
     * @param ApproveGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(ApproveGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $input->principal();
        $resource = new ResourceIdentifier(
            type: ResourceType::GROUP,
            id: (string) $group->groupIdentifier(),
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::APPROVE, $resource, $principal)) {
            throw new UnauthorizedException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->groupService->existsApprovedButNotTranslatedGroup(
            $group->translationSetIdentifier(),
            $group->groupIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedGroupException();
        }

        $group->setStatus(ApprovalStatus::Approved);

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
