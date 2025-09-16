<?php

namespace Businesses\Wiki\Group\UseCase\Command\EditGroup;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Businesses\Wiki\Group\UseCase\Exception\GroupNotFoundException;

class EditGroup implements EditGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    /**
     * @param EditGroupInputPort $input
     * @return Group|null
     * @throws GroupNotFoundException
     */
    public function process(EditGroupInputPort $input): ?Group
    {
        $group = $this->groupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $group->setName($input->name());
        $group->setCompanyIdentifier($input->companyIdentifier());
        $group->setDescription($input->description());
        $group->setSongIdentifiers($input->songIdentifiers());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $group->setImageLink($imageLink);
        }

        $this->groupRepository->save($group);

        return $this->groupRepository->findById($group->groupIdentifier());
    }
}
