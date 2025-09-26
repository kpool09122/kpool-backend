<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;

class EditGroup implements EditGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    /**
     * @param EditGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     */
    public function process(EditGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $group->setName($input->name());
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
