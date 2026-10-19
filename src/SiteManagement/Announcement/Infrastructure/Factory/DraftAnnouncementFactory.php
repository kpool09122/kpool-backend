<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class DraftAnnouncementFactory implements DraftAnnouncementFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(Language $language, Category $category, Title $title, Content $content): DraftAnnouncement
    {
        return new DraftAnnouncement(new AnnouncementIdentifier($this->uuidGenerator->generate()), $language, $category, new AnnouncementContents(new LocalizedAnnouncementContent($language, $title, $content)));
    }
}
