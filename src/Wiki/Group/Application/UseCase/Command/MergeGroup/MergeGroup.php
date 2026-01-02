<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\MergeGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class MergeGroup implements MergeGroupInterface
{
    public function __construct(
        private DraftGroupRepositoryInterface $draftGroupRepository,
        private NormalizationServiceInterface $normalizationService,
        private PrincipalRepositoryInterface  $principalRepository,
        private PolicyEvaluatorInterface      $policyEvaluator,
    ) {
    }

    /**
     * @param MergeGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeGroupInputPort $input): DraftGroup
    {
        $group = $this->draftGroupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::MERGE, $resourceIdentifier)) {
            throw new UnauthorizedException();
        }

        $group->setName($input->name());
        $normalizedName = $this->normalizationService->normalize((string)$group->name(), $group->language());
        $group->setNormalizedName($normalizedName);
        $group->setAgencyIdentifier($input->agencyIdentifier());
        $group->setDescription($input->description());
        $group->setMergerIdentifier($input->principalIdentifier());
        $group->setMergedAt($input->mergedAt());

        $this->draftGroupRepository->save($group);

        return $group;
    }
}
