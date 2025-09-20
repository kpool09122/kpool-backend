<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;

class CreateGroup implements CreateGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private GroupFactoryInterface $groupFactory,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    public function process(CreateGroupInputPort $input): Group
    {
        $group = $this->groupFactory->create(
            $input->translation(),
            $input->name(),
        );
        $group->setAgencyIdentifier($input->agencyIdentifier());
        $group->setDescription($input->description());
        $group->setSongIdentifiers($input->songIdentifiers());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $group->setImageLink($imageLink);
        }

        $this->groupRepository->save($group);

        return $group;
    }
}
