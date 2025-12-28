<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class CreateAnnouncement implements CreateAnnouncementInterface
{
    public function __construct(
        private DraftAnnouncementFactoryInterface $draftAnnouncementFactory,
        private AnnouncementRepositoryInterface   $announcementRepository,
        private UserRepositoryInterface           $userRepository,
    ) {
    }

    public function process(CreateAnnouncementInputPort $input): DraftAnnouncement
    {
        $user = $this->userRepository->findById($input->userIdentifier());
        if (! $user?->isAdmin()) {
            throw new UnauthorizedException();
        }

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
