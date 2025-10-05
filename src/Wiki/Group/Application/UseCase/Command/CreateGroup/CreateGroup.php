<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreateGroup implements CreateGroupInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private DraftGroupFactoryInterface $groupFactory,
        private GroupRepositoryInterface $groupRepository,
    ) {
    }

    /**
     * @param CreateGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     */
    public function process(CreateGroupInputPort $input): DraftGroup
    {
        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: (string) $input->agencyIdentifier(),
            groupIds: [],
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

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
