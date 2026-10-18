<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class AnnouncementRepositoryTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testRoundTripsAndUpdatesOneMultilingualRow(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $repository = $this->app()->make(AnnouncementRepositoryInterface::class);
        $entity = new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $draft->publishedDate());
        $repository->save($entity);
        $loaded = $repository->findById($draft->announcementIdentifier());
        $this->assertNotNull($loaded);
        $this->assertSame($entity->contents()->toArray(), $loaded->contents()->toArray());
        $this->assertSame($entity->sourceLanguage(), $loaded->sourceLanguage());
        $this->assertSame($entity->category(), $loaded->category());
        $this->assertEquals($entity->publishedDate(), $loaded->publishedDate());
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $repository->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $draft->publishedDate()));
        $this->assertCount(3, $repository->findById($draft->announcementIdentifier())?->contents()->toArray() ?? []);
        $this->assertDatabaseCount('announcements', 1);
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingIdReturnsNull(): void
    {
        $this->assertNull($this->app()->make(AnnouncementRepositoryInterface::class)->findById($this->draft()->announcementIdentifier()));
    }
}
