<?php

namespace Businesses\SiteManagement\Announcement\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;

class AnnouncementFactory implements AnnouncementFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        Category $category,
        Title $title,
        Content $content,
        PublishedDate $publishedDate,
    ): Announcement {
        return new Announcement(
            new AnnouncementIdentifier($this->ulidGenerator->generate()),
            $category,
            $title,
            $content,
            $publishedDate,
        );
    }
}
