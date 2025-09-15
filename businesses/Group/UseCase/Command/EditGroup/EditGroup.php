<?php

namespace Businesses\Group\UseCase\Command\EditGroup;

use Businesses\Group\Domain\Entity\Group;
use Businesses\Group\Domain\Repository\GroupRepositoryInterface;
use Businesses\Group\UseCase\Exception\GroupNotFoundException;
use Businesses\Shared\Service\ImageServiceInterface;

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
