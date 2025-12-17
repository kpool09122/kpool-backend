<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class EditGroup implements EditGroupInterface
{
    public function __construct(
        private ImageServiceInterface    $imageService,
        private GroupRepositoryInterface $groupRepository,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    /**
     * @param EditGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::EDIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $group->setName($input->name());
        $normalizedName = $this->normalizationService->normalize((string)$group->name(), $group->language());
        $group->setNormalizedName($normalizedName);
        $group->setAgencyIdentifier($input->agencyIdentifier());
        $group->setDescription($input->description());
        $group->setSongIdentifiers($input->songIdentifiers());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $group->setImagePath($imageLink);
        }

        $this->groupRepository->saveDraft($group);

        return $group;
    }
}
