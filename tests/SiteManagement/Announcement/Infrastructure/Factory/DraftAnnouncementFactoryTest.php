<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DraftAnnouncementFactoryTest extends AnnouncementTestCase
{
    public function testCreatesOneAnnouncementWithOriginalContent(): void
    {
        $factory = $this->app()->make(DraftAnnouncementFactoryInterface::class);
        $announcement = $factory->create(Language::JAPANESE, Category::NEWS, new Title('title'), new Content('body'), $this->draft()->publishedDate());
        $this->assertTrue(UuidValidator::isValid((string) $announcement->announcementIdentifier()));
        $this->assertSame(Language::JAPANESE, $announcement->sourceLanguage());
        $this->assertSame('title', (string) $announcement->localizedContent(Language::JAPANESE)?->title);
        $this->assertCount(1, $announcement->contents()->toArray());
    }
}
