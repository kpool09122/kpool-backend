<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement\GetAnnouncementInterface;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class AnnouncementQueriesTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testDetailSelectsOnlyRequestedLanguage(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $this->app()->make(AnnouncementRepositoryInterface::class)->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $draft->publishedDate()));
        $query = $this->app()->make(GetAnnouncementInterface::class);
        $readModel = $query->process(new GetAnnouncementInput($draft->announcementIdentifier(), Language::KOREAN));
        $this->assertSame('ko title', $readModel->title());
        $this->assertSame('ko body', $readModel->content());
        $this->assertSame((string) $draft->announcementIdentifier(), $readModel->announcementId());
        $this->expectException(AnnouncementNotFoundException::class);
        $query->process(new GetAnnouncementInput($draft->announcementIdentifier(), Language::ENGLISH));
    }

    #[PHPUnitGroup('useDb')]
    public function testDetailDoesNotExposeDraft(): void
    {
        $draft = $this->draft();
        $this->app()->make(DraftAnnouncementRepositoryInterface::class)->save($draft);
        $this->expectException(AnnouncementNotFoundException::class);
        $this->app()->make(GetAnnouncementInterface::class)->process(new GetAnnouncementInput($draft->announcementIdentifier(), Language::JAPANESE));
    }
}
