<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResourceQueryServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SyncOwnedWikiCertifications implements SyncOwnedWikiCertificationsInterface
{
    public function __construct(
        private SyncableOwnedWikiResourceQueryServiceInterface $resourceQueryService,
        private OfficialResourceUpdaterInterface $officialResourceUpdater,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(SyncOwnedWikiCertificationsInputPort $input, SyncOwnedWikiCertificationsOutputPort $output): void
    {
        if ($input->accountCategory() !== AccountCategory::AGENCY) {
            throw new DisallowedException();
        }

        $principal = $this->principalRepository->findById($input->requesterPrincipalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_OWNED_WIKI_SYNC,
            new Resource(
                type: ResourceType::AGENCY,
                requesterAccountCategory: $input->accountCategory(),
            ),
        )) {
            throw new DisallowedException();
        }

        $syncableResources = $this->resourceQueryService->findSyncableResources($input->accountIdentifier());
        $syncableByTranslationSet = [];
        foreach ($syncableResources as $resource) {
            $syncableByTranslationSet[(string) $resource->translationSetIdentifier()] = $resource;
        }

        $requestedByTranslationSet = [];
        foreach ($input->translationSetIdentifiers() as $translationSetIdentifier) {
            $key = (string) $translationSetIdentifier;
            if (! isset($syncableByTranslationSet[$key])) {
                throw new DisallowedException();
            }
            $requestedByTranslationSet[$key] = $syncableByTranslationSet[$key];
        }

        $currentlyOfficial = $this->resourceQueryService->findOfficialResources($input->accountIdentifier(), $syncableResources);
        $currentlyOfficialByKey = [];
        foreach ($currentlyOfficial as $resource) {
            $currentlyOfficialByKey[$resource->key()] = $resource;
        }

        $approved = [];
        $unchanged = [];
        foreach ($requestedByTranslationSet as $resource) {
            if (isset($currentlyOfficialByKey[$resource->key()])) {
                $unchanged[] = $resource;

                continue;
            }

            $this->officialResourceUpdater->markOfficial(
                $resource->resourceType(),
                $resource->translationSetIdentifier(),
                $input->accountIdentifier(),
            );
            $approved[] = $resource;
        }

        $rejected = [];
        foreach ($currentlyOfficial as $resource) {
            if (isset($requestedByTranslationSet[(string) $resource->translationSetIdentifier()])) {
                continue;
            }

            $this->officialResourceUpdater->unmarkOfficial(
                $resource->resourceType(),
                $resource->translationSetIdentifier(),
                $input->accountIdentifier(),
            );
            $rejected[] = $resource;
        }

        $output->setResult(
            $approved,
            $rejected,
            $unchanged,
        );
    }
}
