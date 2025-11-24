<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

readonly class CreateAnnouncement implements CreateAnnouncementInterface
{
    public function __construct(
        private DraftAnnouncementFactoryInterface $draftAnnouncementFactory,
        private AnnouncementRepositoryInterface   $announcementRepository,
    ) {
    }

    public function process(CreateAnnouncementInputPort $input): DraftAnnouncement
    {
        $draftAnnouncement = $this->draftAnnouncementFactory->create(
            $input->translationSetIdentifier(),
            $input->language(),
            $input->category(),
            $input->title(),
            $input->content(),
            $input->publishedDate(),
        );

        $this->announcementRepository->saveDraft($draftAnnouncement);

        return $draftAnnouncement;
    }
}
