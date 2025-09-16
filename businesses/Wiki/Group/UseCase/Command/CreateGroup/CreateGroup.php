<?php

namespace Businesses\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Wiki\Group\Domain\Repository\GroupRepositoryInterface;

class CreateGroup implements CreateGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private GroupFactoryInterface $groupFactory,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    public function process(CreateGroupInputPort $input): ?Group
    {
        $group = $this->groupFactory->create($input->name());
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
