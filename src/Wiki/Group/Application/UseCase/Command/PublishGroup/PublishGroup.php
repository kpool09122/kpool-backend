<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class PublishGroup implements PublishGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private GroupServiceInterface $groupService,
        private GroupFactoryInterface $groupFactory,
    ) {
    }

    /**
     * @param PublishGroupInputPort $input
     * @return Group
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedGroupException
     */
    public function process(PublishGroupInputPort $input): Group
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->groupService->existsApprovedButNotTranslatedGroup(
            $group->translationSetIdentifier(),
            $group->groupIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedGroupException();
        }

        if ($group->publishedGroupIdentifier()) {
            $publishedGroup = $this->groupRepository->findById($input->publishedGroupIdentifier());
            if ($publishedGroup === null) {
                throw new GroupNotFoundException();
            }
            $publishedGroup->setName($group->name());
        } else {
            $publishedGroup = $this->groupFactory->create(
                $group->translationSetIdentifier(),
                $group->translation(),
                $group->name(),
            );
        }
        if ($group->agencyIdentifier()) {
            $publishedGroup->setAgencyIdentifier($group->agencyIdentifier());
        }
        $publishedGroup->setDescription($group->description());
        $publishedGroup->setSongIdentifiers($group->songIdentifiers());
        $publishedGroup->setImageLink($group->imageLink());

        $this->groupRepository->save($publishedGroup);
        $this->groupRepository->deleteDraft($group);

        return $publishedGroup;
    }
}
