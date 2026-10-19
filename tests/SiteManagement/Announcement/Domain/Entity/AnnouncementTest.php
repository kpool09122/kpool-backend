<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\Entity;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class AnnouncementTest extends AnnouncementTestCase
{
    public function testHoldsMultipleLanguagesAndDoesNotFallback(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $announcement = new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $this->publishedDate());
        $this->assertSame(Language::JAPANESE, $announcement->sourceLanguage());
        $this->assertSame('ko title', (string) $announcement->localizedContent(Language::KOREAN)?->title);
        $this->assertNull($announcement->localizedContent(Language::ENGLISH));
        $this->assertCount(2, $announcement->contents()->toArray());
    }

    public function testRequiresSourceContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Announcement($this->draft()->announcementIdentifier(), Language::JAPANESE, Category::NEWS, new AnnouncementContents(), $this->publishedDate());
    }
}
