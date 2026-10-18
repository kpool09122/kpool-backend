<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInterface;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class GetDraftAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testReturnsAllAvailableContentsOfAnUntranslatedDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT);
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $this->app()->make(DraftAnnouncementRepositoryInterface::class)->save($draft);
        $result = $this->app()->make(GetDraftAnnouncementInterface::class)->process(new GetDraftAnnouncementInput($principal, $draft->announcementIdentifier()))->toArray();
        $this->assertSame((string) $draft->announcementIdentifier(), $result['announcementIdentifier']);
        $this->assertSame('ja', $result['sourceLanguage']);
        $this->assertEquals($draft->contents()->toArray(), $result['contents']);
        $this->assertArrayNotHasKey('en', $result['contents']);
    }

    #[PHPUnitGroup('useDb')]
    public function testPublishedOnlyAnnouncementIsNotADraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT);
        $draft = $this->draft();
        $this->app()->make(AnnouncementRepositoryInterface::class)->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $draft->publishedDate()));
        $this->expectException(AnnouncementNotFoundException::class);
        $this->app()->make(GetDraftAnnouncementInterface::class)->process(new GetDraftAnnouncementInput($principal, $draft->announcementIdentifier()));
    }

    #[PHPUnitGroup('useDb')]
    public function testDeletedDraftIsNotFound(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT);
        $draft = $this->draft();
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $repository->delete($draft);
        $this->expectException(AnnouncementNotFoundException::class);
        $this->app()->make(GetDraftAnnouncementInterface::class)->process(new GetDraftAnnouncementInput($principal, $draft->announcementIdentifier()));
    }

    public function testDeniesBeforeReadingDrafts(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(GetDraftAnnouncementInterface::class)->process(new GetDraftAnnouncementInput($principal, $this->draft()->announcementIdentifier()));
    }
}
