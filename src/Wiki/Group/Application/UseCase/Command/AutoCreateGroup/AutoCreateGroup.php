<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\AutoGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class AutoCreateGroup implements AutoCreateGroupInterface
{
    public function __construct(
        private AutoGroupCreationServiceInterface $automaticDraftGroupCreationService,
        private DraftGroupFactoryInterface        $draftGroupFactory,
        private DraftGroupRepositoryInterface     $groupRepository,
        private PrincipalRepositoryInterface      $principalRepository,
        private PolicyEvaluatorInterface          $policyEvaluator,
        private SlugGeneratorServiceInterface     $slugGeneratorService,
    ) {
    }

    /**
     * @param AutoCreateGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateGroupInputPort $input): DraftGroup
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: $principal->agencyId(),
            groupIds: $principal->groupIds(),
            talentIds: $principal->talentIds(),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::AUTOMATIC_CREATE, $resource)) {
            throw new UnauthorizedException();
        }

        $payload = $input->payload();
        $generatedData = $this->automaticDraftGroupCreationService->generate($payload);

        $slugSource = $generatedData->alphabetName() ?? (string)$payload->name();
        $slug = $this->slugGeneratorService->generate($slugSource);

        $draftGroup = $this->draftGroupFactory->create(
            editorIdentifier: null,
            language: $payload->language(),
            name: $payload->name(),
            slug: $slug,
        );

        if ($payload->agencyIdentifier()) {
            $draftGroup->setAgencyIdentifier($payload->agencyIdentifier());
        }

        $description = $generatedData->description() ?? '';
        $draftGroup->setDescription(new Description($description));

        $this->groupRepository->save($draftGroup);

        return $draftGroup;
    }
}
