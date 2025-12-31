<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class AnnouncementFactory implements AnnouncementFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param Category $category
     * @param Title $title
     * @param Content $content
     * @param PublishedDate $publishedDate
     * @return Announcement
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        Category                 $category,
        Title                    $title,
        Content                  $content,
        PublishedDate            $publishedDate,
    ): Announcement {
        return new Announcement(
            new AnnouncementIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
        );
    }
}
