<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class AnnouncementFactoryTest extends AnnouncementTestCase
{
    public function testPublicationKeepsIdentityAndAllLanguages(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $factory = $this->app()->make(AnnouncementFactoryInterface::class);
        $announcement = $factory->create($draft);
        $this->assertSame($draft->announcementIdentifier(), $announcement->announcementIdentifier());
        $this->assertSame($draft->contents(), $announcement->contents());
        $this->assertSame($draft->sourceLanguage(), $announcement->sourceLanguage());
        $this->assertSame($draft->category(), $announcement->category());
        $this->assertSame($draft->publishedDate(), $announcement->publishedDate());
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $this->assertNull($announcement->localizedContent(Language::KOREAN));
    }
}
