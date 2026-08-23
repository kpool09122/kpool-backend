<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class RequestCertification implements RequestCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
        private OfficialCertificationFactoryInterface $factory,
        private WikiRepositoryInterface $wikiRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws OfficialCertificationAlreadyRequestedException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(RequestCertificationInputPort $input, RequestCertificationOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->requesterPrincipalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $wikis = $this->wikiRepository->findByTranslationSetIdentifier($input->translationSetIdentifier());
        $wiki = $this->representativeWiki($wikis, $input->resourceType());
        if ($wiki === null) {
            throw new DisallowedException();
        }

        if (! $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_REQUEST,
            $this->authorizationResource($wiki->resourceType(), $wiki->wikiIdentifier(), $wiki->basic()),
        )) {
            throw new DisallowedException();
        }

        $existing = $this->repository->findByResource(
            $input->resourceType(),
            $input->translationSetIdentifier(),
        );

        if ($existing !== null) {
            throw new OfficialCertificationAlreadyRequestedException();
        }

        $certification = $this->factory->create(
            $input->resourceType(),
            $input->translationSetIdentifier(),
            $input->ownerAccountIdentifier(),
        );

        $this->repository->save($certification);

        $output->setOfficialCertification($certification);
    }

    /**
     * @param Wiki[] $wikis
     */
    private function representativeWiki(array $wikis, ResourceType $resourceType): ?Wiki
    {
        foreach ($wikis as $wiki) {
            if ($wiki->resourceType() === $resourceType) {
                return $wiki;
            }
        }

        return null;
    }

    private function authorizationResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier, mixed $basic): Resource
    {
        $selfIdentifier = (string) $wikiIdentifier;

        return match ($resourceType) {
            ResourceType::AGENCY => new Resource(
                type: ResourceType::AGENCY,
                agencyId: $selfIdentifier,
            ),
            ResourceType::GROUP => new Resource(
                type: ResourceType::GROUP,
                agencyId: $basic instanceof GroupBasic && $basic->agencyIdentifier() !== null ? (string) $basic->agencyIdentifier() : null,
                groupIds: [$selfIdentifier],
            ),
            ResourceType::TALENT => new Resource(
                type: ResourceType::TALENT,
                agencyId: $basic instanceof TalentBasic && $basic->agencyIdentifier() !== null ? (string) $basic->agencyIdentifier() : null,
                groupIds: $basic instanceof TalentBasic ? array_map(static fn (WikiIdentifier $id): string => (string) $id, $basic->groupIdentifiers()) : [],
                talentIds: [$selfIdentifier],
            ),
            ResourceType::SONG => new Resource(
                type: ResourceType::SONG,
                agencyId: $basic instanceof SongBasic && $basic->agencyIdentifier() !== null ? (string) $basic->agencyIdentifier() : null,
                groupIds: $basic instanceof SongBasic ? array_map(static fn (WikiIdentifier $id): string => (string) $id, $basic->groupIdentifiers()) : [],
                talentIds: $basic instanceof SongBasic ? array_map(static fn (WikiIdentifier $id): string => (string) $id, $basic->talentIdentifiers()) : [],
            ),
            default => new Resource(type: $resourceType),
        };
    }
}
