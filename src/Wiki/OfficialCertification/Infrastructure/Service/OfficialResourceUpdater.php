<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class OfficialResourceUpdater implements OfficialResourceUpdaterInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private GroupRepositoryInterface $groupRepository,
        private TalentRepositoryInterface $talentRepository,
        private SongRepositoryInterface $songRepository,
    ) {
    }

    public function markOfficial(
        ResourceType $type,
        ResourceIdentifier $id,
        AccountIdentifier $owner,
    ): void {
        switch ($type) {
            case ResourceType::AGENCY:
                $agency = $this->agencyRepository->findById(new AgencyIdentifier((string) $id));
                if ($agency === null || $agency->isOfficial()) {
                    return;
                }
                $agency->markOfficial($owner);
                $this->agencyRepository->save($agency);

                return;

            case ResourceType::GROUP:
                $group = $this->groupRepository->findById(new GroupIdentifier((string) $id));
                if ($group === null || $group->isOfficial()) {
                    return;
                }
                $group->markOfficial($owner);
                $this->groupRepository->save($group);

                return;

            case ResourceType::TALENT:
                $talent = $this->talentRepository->findById(new TalentIdentifier((string) $id));
                if ($talent === null || $talent->isOfficial()) {
                    return;
                }
                $talent->markOfficial($owner);
                $this->talentRepository->save($talent);

                return;

            case ResourceType::SONG:
                $song = $this->songRepository->findById(new SongIdentifier((string) $id));
                if ($song === null || $song->isOfficial()) {
                    return;
                }
                $song->markOfficial($owner);
                $this->songRepository->save($song);

                return;
        }
    }
}
