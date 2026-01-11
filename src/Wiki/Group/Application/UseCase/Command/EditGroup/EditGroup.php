<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class EditGroup implements EditGroupInterface
{
    public function __construct(
        private ImageServiceInterface         $imageService,
        private DraftGroupRepositoryInterface $groupRepository,
        private NormalizationServiceInterface $normalizationService,
        private PrincipalRepositoryInterface  $principalRepository,
        private PolicyEvaluatorInterface      $policyEvaluator,
    ) {
    }

    /**
     * @param EditGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(EditGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::EDIT, $resource)) {
            throw new UnauthorizedException();
        }

        $group->setName($input->name());
        $normalizedName = $this->normalizationService->normalize((string)$group->name(), $group->language());
        $group->setNormalizedName($normalizedName);
        $group->setAgencyIdentifier($input->agencyIdentifier());
        $group->setDescription($input->description());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $group->setImagePath($imageLink);
        }

        $this->groupRepository->save($group);

        return $group;
    }
}
