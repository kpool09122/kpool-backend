<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Service;

use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ImageAuthorizationResourceBuilder implements ImageAuthorizationResourceBuilderInterface
{
    public function __construct(
        private WikiRepositoryInterface $wikiRepository,
        private DraftWikiRepositoryInterface $draftWikiRepository,
    ) {
    }

    public function buildFromDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): Resource
    {
        $draftWiki = $this->draftWikiRepository->findById(
            new DraftWikiIdentifier((string) $draftResourceIdentifier)
        );

        if ($draftWiki === null) {
            return new Resource(type: ResourceType::IMAGE);
        }

        $selfIdentifier = $draftWiki->publishedWikiIdentifier() !== null
            ? (string) $draftWiki->publishedWikiIdentifier()
            : (string) $draftWiki->wikiIdentifier();

        return $this->buildResourceFromBasic(
            $draftWiki->resourceType(),
            $selfIdentifier,
            $draftWiki->basic(),
        );
    }

    public function buildFromDraftImage(DraftImage $draftImage): Resource
    {
        return $this->buildFromDraftResource(
            $draftImage->resourceType(),
            $draftImage->draftResourceIdentifier(),
        );
    }

    public function buildFromImage(Image $image): Resource
    {
        $wiki = $this->wikiRepository->findById(
            new WikiIdentifier((string) $image->resourceIdentifier())
        );

        if ($wiki === null) {
            return new Resource(type: ResourceType::IMAGE);
        }

        return $this->buildResourceFromBasic(
            $wiki->resourceType(),
            (string) $wiki->wikiIdentifier(),
            $wiki->basic(),
        );
    }

    private function buildResourceFromBasic(ResourceType $resourceType, string $selfIdentifier, BasicInterface $basic): Resource
    {
        return match ($resourceType) {
            ResourceType::AGENCY => new Resource(
                type: ResourceType::IMAGE,
                agencyId: $selfIdentifier,
            ),
            ResourceType::GROUP => new Resource(
                type: ResourceType::IMAGE,
                agencyId: $basic instanceof GroupBasic && $basic->agencyIdentifier() !== null
                    ? (string) $basic->agencyIdentifier()
                    : null,
                groupIds: [$selfIdentifier],
            ),
            ResourceType::TALENT => new Resource(
                type: ResourceType::IMAGE,
                agencyId: $basic instanceof TalentBasic && $basic->agencyIdentifier() !== null
                    ? (string) $basic->agencyIdentifier()
                    : null,
                groupIds: $basic instanceof TalentBasic
                    ? array_map(static fn ($id) => (string) $id, $basic->groupIdentifiers())
                    : [],
                talentIds: [$selfIdentifier],
            ),
            ResourceType::SONG => new Resource(
                type: ResourceType::IMAGE,
                agencyId: $basic instanceof SongBasic && $basic->agencyIdentifier() !== null
                    ? (string) $basic->agencyIdentifier()
                    : null,
                groupIds: $basic instanceof SongBasic
                    ? array_map(static fn ($id) => (string) $id, $basic->groupIdentifiers())
                    : [],
                talentIds: $basic instanceof SongBasic
                    ? array_map(static fn ($id) => (string) $id, $basic->talentIdentifiers())
                    : [],
            ),
            default => new Resource(type: ResourceType::IMAGE),
        };
    }
}
