<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

interface CreateAnnouncementOutputPort
{
    public function setDraftAnnouncement(DraftAnnouncement $draftAnnouncement): void;

    public function draftAnnouncement(): ?DraftAnnouncement;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
