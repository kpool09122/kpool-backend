<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class AutoCreateWiki implements AutoCreateWikiInterface
{
    public function __construct(
        private AutoWikiCreationServiceInterface $automaticDraftWikiCreationService,
        private DraftWikiFactoryInterface        $draftWikiFactory,
        private DraftWikiRepositoryInterface     $draftWikiRepository,
        private NormalizationServiceInterface    $normalizationService,
        private PrincipalRepositoryInterface     $principalRepository,
        private PolicyEvaluatorInterface         $policyEvaluator,
        private SlugGeneratorServiceInterface    $slugGeneratorService,
    ) {
    }

    /**
     * @param AutoCreateWikiInputPort $input
     * @param AutoCreateWikiOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateWikiInputPort $input, AutoCreateWikiOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $payload = $input->payload();

        $resource = new Resource(
            type: $payload->resourceType(),
            agencyId: $payload->agencyIdentifier() ? (string) $payload->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $payload->talentIdentifiers(),
            ),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::AUTOMATIC_CREATE, $resource)) {
            throw new DisallowedException();
        }

        $generatedData = $this->automaticDraftWikiCreationService->generate($payload);

        $slugSource = $generatedData->alphabetName() ?? (string) $payload->name();
        $slug = $this->slugGeneratorService->generate($slugSource, $payload->resourceType());

        $generatedBasic = $generatedData->basic();
        $basicArray = $generatedBasic->toArray();
        foreach ($generatedBasic->normalizableKeys() as $sourceKey => $normalizedKey) {
            $basicArray[$normalizedKey] = $this->normalizationService->normalize(
                $basicArray[$sourceKey] ?? '',
                $payload->language(),
            );
        }
        $basic = $generatedBasic::fromArray($basicArray);

        $draftWiki = $this->draftWikiFactory->create(
            editorIdentifier: null,
            language: $payload->language(),
            basic: $basic,
            slug: $slug,
        );

        $draftWiki->setSections($generatedData->sections());

        $this->draftWikiRepository->save($draftWiki);

        $output->setDraftWiki($draftWiki);
    }
}
