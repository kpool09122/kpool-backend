<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class PublishGroup implements PublishGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface         $groupRepository,
        private GroupServiceInterface            $groupService,
        private GroupFactoryInterface            $groupFactory,
        private GroupHistoryRepositoryInterface  $groupHistoryRepository,
        private GroupHistoryFactoryInterface     $groupHistoryFactory,
        private GroupSnapshotFactoryInterface    $groupSnapshotFactory,
        private GroupSnapshotRepositoryInterface $groupSnapshotRepository,
        private PrincipalRepositoryInterface     $principalRepository,
    ) {
    }

    /**
     * @param PublishGroupInputPort $input
     * @return Group
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
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

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::PUBLISH, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
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

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->groupSnapshotFactory->create($publishedGroup);
            $this->groupSnapshotRepository->save($snapshot);

            $publishedGroup->setName($group->name());
            $publishedGroup->setNormalizedName($group->normalizedName());
            $publishedGroup->updateVersion();
        } else {
            $publishedGroup = $this->groupFactory->create(
                $group->translationSetIdentifier(),
                $group->language(),
                $group->name(),
            );
        }
        if ($group->agencyIdentifier()) {
            $publishedGroup->setAgencyIdentifier($group->agencyIdentifier());
        }
        $publishedGroup->setDescription($group->description());
        $publishedGroup->setSongIdentifiers($group->songIdentifiers());
        $publishedGroup->setImagePath($group->imagePath());

        $this->groupRepository->save($publishedGroup);

        $history = $this->groupHistoryFactory->create(
            new EditorIdentifier((string)$input->principalIdentifier()),
            $group->editorIdentifier(),
            $group->publishedGroupIdentifier(),
            $group->groupIdentifier(),
            $group->status(),
            null,
            $group->name(),
        );
        $this->groupHistoryRepository->save($history);

        $this->groupRepository->deleteDraft($group);

        return $publishedGroup;
    }
}
