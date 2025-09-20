<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

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
