<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class DraftAnnouncementFactory implements DraftAnnouncementFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @param Language $language
     * @param Category $category
     * @param Title $title
     * @param Content $content
     * @param PublishedDate $publishedDate
     * @return DraftAnnouncement
     */
    public function create(
        ?TranslationSetIdentifier $translationSetIdentifier,
        Language                  $language,
        Category                  $category,
        Title                     $title,
        Content                   $content,
        PublishedDate             $publishedDate,
    ): DraftAnnouncement {
        return new DraftAnnouncement(
            new AnnouncementIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->ulidGenerator->generate()),
            $language,
            $category,
            $title,
            $content,
            $publishedDate,
        );
    }
}
