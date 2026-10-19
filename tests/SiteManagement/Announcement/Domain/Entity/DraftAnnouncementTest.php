<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\Entity;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DraftAnnouncementTest extends AnnouncementTestCase
{
    public function testHoldsMultipleLanguagesAndDoesNotFallback(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $announcement = $draft;
        $this->assertSame(Language::JAPANESE, $announcement->sourceLanguage());
        $this->assertSame('ko title', (string) $announcement->localizedContent(Language::KOREAN)?->title);
        $this->assertNull($announcement->localizedContent(Language::ENGLISH));
        $this->assertCount(2, $announcement->contents()->toArray());
    }

    public function testRequiresSourceContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DraftAnnouncement($this->draft()->announcementIdentifier(), Language::JAPANESE, Category::NEWS, new AnnouncementContents());
    }

    public function testEditsOneLanguageAndCommonMetadata(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $draft->setLocalizedContent(new LocalizedAnnouncementContent(Language::JAPANESE, new Title('updated'), new Content('updated body')));
        $draft->setCategory(Category::UPDATES);
        $this->assertSame('updated', (string) $draft->localizedContent(Language::JAPANESE)?->title);
        $this->assertSame('en title', (string) $draft->localizedContent(Language::ENGLISH)?->title);
        $this->assertSame(Category::UPDATES, $draft->category());
    }
}
