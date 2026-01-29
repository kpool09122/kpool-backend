<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\AutoTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;

readonly class AutoCreateTalent implements AutoCreateTalentInterface
{
    public function __construct(
        private AutoTalentCreationServiceInterface $automaticDraftTalentCreationService,
        private DraftTalentFactoryInterface        $draftTalentFactory,
        private DraftTalentRepositoryInterface     $draftTalentRepository,
        private PrincipalRepositoryInterface       $principalRepository,
        private PolicyEvaluatorInterface           $policyEvaluator,
        private SlugGeneratorServiceInterface      $slugGeneratorService,
    ) {
    }

    /**
     * @param AutoCreateTalentInputPort $input
     * @return DraftTalent
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateTalentInputPort $input): DraftTalent
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: $principal->agencyId(),
            groupIds: $principal->groupIds(),
            talentIds: $principal->talentIds(),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::AUTOMATIC_CREATE, $resource)) {
            throw new DisallowedException();
        }

        $payload = $input->payload();
        $generatedData = $this->automaticDraftTalentCreationService->generate($payload);

        $slugSource = $generatedData->alphabetName() ?? (string)$payload->name();
        $slug = $this->slugGeneratorService->generate($slugSource);

        $draftTalent = $this->draftTalentFactory->create(
            editorIdentifier: null,
            slug: $slug,
            language: $payload->language(),
            name: $payload->name(),
        );

        $realName = $generatedData->realName() ?? '';
        $draftTalent->setRealName(new RealName($realName));

        if ($payload->agencyIdentifier() !== null) {
            $draftTalent->setAgencyIdentifier($payload->agencyIdentifier());
        }

        $draftTalent->setGroupIdentifiers($payload->groupIdentifiers());

        if ($generatedData->birthday() !== null) {
            $draftTalent->setBirthday(new Birthday(new DateTimeImmutable($generatedData->birthday())));
        }

        $career = $generatedData->description() ?? '';
        $draftTalent->setCareer(new Career($career));

        $this->draftTalentRepository->save($draftTalent);

        return $draftTalent;
    }
}
