<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use DateTimeImmutable;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AutomaticDraftAgencyCreationServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class AutomaticCreateDraftAgency implements AutomaticCreateDraftAgencyInterface
{
    public function __construct(
        private AutomaticDraftAgencyCreationServiceInterface $automaticDraftAgencyCreationService,
        private DraftAgencyFactoryInterface $draftAgencyFactory,
        private DraftAgencyRepositoryInterface $agencyRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private NormalizationServiceInterface $normalizationService,
        private SlugGeneratorServiceInterface $slugGeneratorService,
    ) {
    }

    /**
     * @param AutomaticCreateDraftAgencyInputPort $input
     * @return DraftAgency
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftAgencyInputPort $input): DraftAgency
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::AGENCY,
            agencyId: $principal->agencyId(),
            groupIds: $principal->groupIds(),
            talentIds: $principal->talentIds(),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::AUTOMATIC_CREATE, $resource)) {
            throw new DisallowedException();
        }

        $payload = $input->payload();
        $generatedData = $this->automaticDraftAgencyCreationService->generate($payload);

        $slugSource = $generatedData->alphabetName() ?? (string)$payload->name();
        $slug = $this->slugGeneratorService->generate($slugSource);

        $draftAgency = $this->draftAgencyFactory->create(
            editorIdentifier: null,
            language: $payload->language(),
            agencyName: $payload->name(),
            slug: $slug,
        );

        $ceoName = $generatedData->ceoName() ?? '';
        $draftAgency->setCEO(new CEO($ceoName));
        $normalizedCEO = $this->normalizationService->normalize($ceoName, $payload->language());
        $draftAgency->setNormalizedCEO($normalizedCEO);

        if ($generatedData->foundedYear() !== null) {
            $draftAgency->setFoundedIn(new FoundedIn(new DateTimeImmutable($generatedData->foundedYear() . '-01-01')));
        }

        $description = $generatedData->description() ?? '';
        $draftAgency->setDescription(new Description($description));

        $this->agencyRepository->save($draftAgency);

        return $draftAgency;
    }
}
