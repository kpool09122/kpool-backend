<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Service;

use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class ImageAuthorizationResourceBuilder implements ImageAuthorizationResourceBuilderInterface
{
    public function __construct(
        private DraftAgencyRepositoryInterface $draftAgencyRepository,
        private DraftGroupRepositoryInterface $draftGroupRepository,
        private DraftTalentRepositoryInterface $draftTalentRepository,
        private DraftSongRepositoryInterface $draftSongRepository,
        private AgencyRepositoryInterface $agencyRepository,
        private GroupRepositoryInterface $groupRepository,
        private TalentRepositoryInterface $talentRepository,
        private SongRepositoryInterface $songRepository,
    ) {
    }

    public function buildFromDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): Resource
    {
        if ($resourceType === ResourceType::AGENCY) {
            $draftAgency = $this->draftAgencyRepository->findById(
                new AgencyIdentifier((string) $draftResourceIdentifier)
            );

            if ($draftAgency === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: (string) $draftAgency->agencyIdentifier(),
            );
        }

        if ($resourceType === ResourceType::GROUP) {
            $draftGroup = $this->draftGroupRepository->findById(
                new GroupIdentifier((string) $draftResourceIdentifier)
            );

            if ($draftGroup === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $draftGroup->agencyIdentifier() !== null
                    ? (string) $draftGroup->agencyIdentifier()
                    : null,
                groupIds: [(string) $draftGroup->groupIdentifier()],
            );
        }

        if ($resourceType === ResourceType::TALENT) {
            $draftTalent = $this->draftTalentRepository->findById(
                new TalentIdentifier((string) $draftResourceIdentifier)
            );

            if ($draftTalent === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $draftTalent->agencyIdentifier() !== null
                    ? (string) $draftTalent->agencyIdentifier()
                    : null,
                groupIds: array_map(
                    static fn ($groupIdentifier) => (string) $groupIdentifier,
                    $draftTalent->groupIdentifiers()
                ),
                talentIds: [(string) $draftTalent->talentIdentifier()],
            );
        }

        if ($resourceType === ResourceType::SONG) {
            $draftSong = $this->draftSongRepository->findById(
                new SongIdentifier((string) $draftResourceIdentifier)
            );

            if ($draftSong === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $draftSong->agencyIdentifier() !== null
                    ? (string) $draftSong->agencyIdentifier()
                    : null,
                groupIds: $draftSong->groupIdentifier() !== null
                    ? [(string) $draftSong->groupIdentifier()]
                    : [],
                talentIds: $draftSong->talentIdentifier() !== null
                    ? [(string) $draftSong->talentIdentifier()]
                    : [],
            );
        }

        return new Resource(type: ResourceType::IMAGE);
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
        $resourceType = $image->resourceType();
        $resourceIdentifier = $image->resourceIdentifier();

        if ($resourceType === ResourceType::AGENCY) {
            $agency = $this->agencyRepository->findById(
                new AgencyIdentifier((string) $resourceIdentifier)
            );

            if ($agency === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: (string) $agency->agencyIdentifier(),
            );
        }

        if ($resourceType === ResourceType::GROUP) {
            $group = $this->groupRepository->findById(
                new GroupIdentifier((string) $resourceIdentifier)
            );

            if ($group === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $group->agencyIdentifier() !== null
                    ? (string) $group->agencyIdentifier()
                    : null,
                groupIds: [(string) $group->groupIdentifier()],
            );
        }

        if ($resourceType === ResourceType::TALENT) {
            $talent = $this->talentRepository->findById(
                new TalentIdentifier((string) $resourceIdentifier)
            );

            if ($talent === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $talent->agencyIdentifier() !== null
                    ? (string) $talent->agencyIdentifier()
                    : null,
                groupIds: array_map(
                    static fn ($groupIdentifier) => (string) $groupIdentifier,
                    $talent->groupIdentifiers()
                ),
                talentIds: [(string) $talent->talentIdentifier()],
            );
        }

        if ($resourceType === ResourceType::SONG) {
            $song = $this->songRepository->findById(
                new SongIdentifier((string) $resourceIdentifier)
            );

            if ($song === null) {
                return new Resource(type: ResourceType::IMAGE);
            }

            return new Resource(
                type: ResourceType::IMAGE,
                agencyId: $song->agencyIdentifier() !== null
                    ? (string) $song->agencyIdentifier()
                    : null,
                groupIds: $song->groupIdentifier() !== null
                    ? [(string) $song->groupIdentifier()]
                    : [],
                talentIds: $song->talentIdentifier() !== null
                    ? [(string) $song->talentIdentifier()]
                    : [],
            );
        }

        return new Resource(type: ResourceType::IMAGE);
    }
}
