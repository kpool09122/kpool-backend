<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Businesses\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

class CreateAnnouncement implements CreateAnnouncementInterface
{
    public function __construct(
        private AnnouncementFactoryInterface $announcementFactory,
        private AnnouncementRepositoryInterface $announcementRepository,
    ) {
    }

    public function process(CreateAnnouncementInputPort $input): Announcement
    {
        $announcement = $this->announcementFactory->create(
            $input->translation(),
            $input->category(),
            $input->title(),
            $input->content(),
            $input->publishedDate(),
        );

        $this->announcementRepository->save($announcement);

        return $announcement;
    }
}
