<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Mockery;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement\CreateAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class CreateAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testCreatesOneDraftWithOneOriginalLanguage(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_CREATE);
        $output = new CreateAnnouncementOutput();
        $this->app()->make(CreateAnnouncementInterface::class)->process(new CreateAnnouncementInput($principal, Language::JAPANESE, Category::NEWS, new Title("new title"), new Content("new body"), $this->draft()->publishedDate()), $output);
        $draft = $output->draftAnnouncement();
        $this->assertNotNull($draft);
        $this->assertSame('new title', (string) $draft->localizedContent(Language::JAPANESE)?->title);
        $this->assertNull($draft->localizedContent(Language::KOREAN));
        $this->assertDatabaseCount('draft_announcements', 1);
        $this->assertDatabaseCount('announcements', 0);
        $loaded = $this->app()->make(DraftAnnouncementRepositoryInterface::class)->findById($draft->announcementIdentifier());
        $this->assertSame($draft->contents()->toArray(), $loaded?->contents()->toArray());
    }

    public function testDeniesWithoutPermission(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_CREATE, false);
        $repository = Mockery::mock(DraftAnnouncementRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(DraftAnnouncementRepositoryInterface::class, $repository);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(CreateAnnouncementInterface::class)->process(new CreateAnnouncementInput($principal, Language::JAPANESE, Category::NEWS, new Title("new title"), new Content("new body"), $this->draft()->publishedDate()), new CreateAnnouncementOutput());
    }

    public function testDeniesMissingPrincipal(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_CREATE, true, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(CreateAnnouncementInterface::class)->process(new CreateAnnouncementInput($principal, Language::JAPANESE, Category::NEWS, new Title("new title"), new Content("new body"), $this->draft()->publishedDate()), new CreateAnnouncementOutput());
    }
}
