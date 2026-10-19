<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Factory;

use Carbon\CarbonImmutable;
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
        $now = CarbonImmutable::parse('2026-10-19 12:34:56');
        CarbonImmutable::setTestNow($now);

        try {
            $announcement = $factory->create($draft);
        } finally {
            CarbonImmutable::setTestNow();
        }
        $this->assertSame($draft->announcementIdentifier(), $announcement->announcementIdentifier());
        $this->assertSame($draft->contents(), $announcement->contents());
        $this->assertSame($draft->sourceLanguage(), $announcement->sourceLanguage());
        $this->assertSame($draft->category(), $announcement->category());
        $this->assertEquals($now, $announcement->publishedDate()->value());
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $this->assertNull($announcement->localizedContent(Language::KOREAN));
    }
}
