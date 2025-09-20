<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class AnnouncementFactory implements AnnouncementFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        Translation $translation,
        Category $category,
        Title $title,
        Content $content,
        PublishedDate $publishedDate,
    ): Announcement {
        return new Announcement(
            new AnnouncementIdentifier($this->ulidGenerator->generate()),
            $translation,
            $category,
            $title,
            $content,
            $publishedDate,
        );
    }
}
