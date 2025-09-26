<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;

class CreateGroup implements CreateGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private DraftGroupFactoryInterface $groupFactory,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    public function process(CreateGroupInputPort $input): DraftGroup
    {
        $group = $this->groupFactory->create(
            $input->editorIdentifier(),
            $input->translation(),
            $input->name(),
        );
        if ($input->publishedGroupIdentifier()) {
            $publishedGroup = $this->groupRepository->findById($input->publishedGroupIdentifier());
            if ($publishedGroup) {
                $group->setPublishedGroupIdentifier($publishedGroup->groupIdentifier());
            }
        }
        $group->setAgencyIdentifier($input->agencyIdentifier());
        $group->setDescription($input->description());
        $group->setSongIdentifiers($input->songIdentifiers());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $group->setImageLink($imageLink);
        }

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
