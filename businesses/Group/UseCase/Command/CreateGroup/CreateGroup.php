<?php

namespace Businesses\Group\UseCase\Command\CreateGroup;

use Businesses\Group\Domain\Entity\Group;
use Businesses\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Group\Domain\Repository\GroupRepositoryInterface;
use Businesses\Shared\Service\ImageServiceInterface;

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
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
        }

        $group = $this->groupFactory->create(
            $input->name(),
            $input->companyIdentifier(),
            $input->description(),
            $input->songIdentifiers(),
            $imageLink ?? null,
        );

        $this->groupRepository->save($group);

        return $this->groupRepository->findById($group->groupIdentifier());
    }
}
