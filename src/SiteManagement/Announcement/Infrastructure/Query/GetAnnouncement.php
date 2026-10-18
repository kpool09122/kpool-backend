<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Query;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInputPort;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

readonly class GetAnnouncement implements GetAnnouncementInterface
{
    public function __construct(private AnnouncementRepositoryInterface $announcementRepository)
    {
    }

    /** @throws AnnouncementNotFoundException */
    public function process(GetAnnouncementInputPort $input): AnnouncementReadModel
    {
        $announcement = $this->announcementRepository->findById($input->announcementIdentifier());
        $readModel = $announcement === null ? null : AnnouncementReadModel::fromAnnouncement($announcement, $input->language());
        if ($readModel === null) {
            throw new AnnouncementNotFoundException();
        }

        return $readModel;
    }
}
