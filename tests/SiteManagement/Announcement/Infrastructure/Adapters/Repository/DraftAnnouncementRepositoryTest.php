<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DraftAnnouncementRepositoryTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testRoundTripsAndUpdatesOneMultilingualRow(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $entity = $draft;
        $repository->save($entity);
        $loaded = $repository->findById($draft->announcementIdentifier());
        $this->assertNotNull($loaded);
        $this->assertSame($entity->contents()->toArray(), $loaded->contents()->toArray());
        $this->assertSame($entity->sourceLanguage(), $loaded->sourceLanguage());
        $this->assertSame($entity->category(), $loaded->category());
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $repository->save($draft);
        $this->assertCount(3, $repository->findById($draft->announcementIdentifier())?->contents()->toArray() ?? []);
        $this->assertDatabaseCount('draft_announcements', 1);
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingIdReturnsNull(): void
    {
        $this->assertNull($this->app()->make(DraftAnnouncementRepositoryInterface::class)->findById($this->draft()->announcementIdentifier()));
    }

    #[PHPUnitGroup('useDb')]
    public function testDeletingDraftPreservesPublishedRowWithSameId(): void
    {
        $draft = $this->draft();
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $publishedRepository = $this->app()->make(AnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $publishedRepository->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $this->publishedDate()));
        $repository->delete($draft);
        $this->assertNull($repository->findById($draft->announcementIdentifier()));
        $this->assertNotNull($publishedRepository->findById($draft->announcementIdentifier()));
    }
}
