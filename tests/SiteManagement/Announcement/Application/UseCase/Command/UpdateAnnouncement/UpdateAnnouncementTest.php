<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Mockery;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementOutput;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class UpdateAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testEditsSelectedLanguageAndKeepsOtherContents(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_UPDATE);
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::ENGLISH));
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $output = new UpdateAnnouncementOutput();
        $this->app()->make(UpdateAnnouncementInterface::class)->process(new UpdateAnnouncementInput($principal, $this->draft()->announcementIdentifier(), Language::KOREAN, Category::UPDATES, new Title("manual ko"), new Content("manual body"), $this->draft()->publishedDate()), $output);
        $loaded = $repository->findById($draft->announcementIdentifier());
        $this->assertSame('manual ko', (string) $loaded?->localizedContent(Language::KOREAN)?->title);
        $this->assertSame('ja title', (string) $loaded?->localizedContent(Language::JAPANESE)?->title);
        $this->assertSame('en title', (string) $loaded?->localizedContent(Language::ENGLISH)?->title);
        $this->assertSame(Category::UPDATES, $loaded?->category());
        $updated = $output->draftAnnouncement();
        $this->assertNotNull($updated);
        $this->assertSame($loaded->contents()->toArray(), $updated->contents()->toArray());
        $this->assertDatabaseCount('draft_announcements', 1);
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_UPDATE);
        $this->expectException(AnnouncementNotFoundException::class);
        $output = new UpdateAnnouncementOutput();
        $this->app()->make(UpdateAnnouncementInterface::class)->process(new UpdateAnnouncementInput($principal, $this->draft()->announcementIdentifier(), Language::KOREAN, Category::UPDATES, new Title("manual ko"), new Content("manual body"), $this->draft()->publishedDate()), $output);
    }

    public function testDeniesWithoutPermission(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_UPDATE, false);
        $repository = Mockery::mock(DraftAnnouncementRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(DraftAnnouncementRepositoryInterface::class, $repository);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(UpdateAnnouncementInterface::class)->process(new UpdateAnnouncementInput($principal, $this->draft()->announcementIdentifier(), Language::KOREAN, Category::UPDATES, new Title("manual ko"), new Content("manual body"), $this->draft()->publishedDate()), new UpdateAnnouncementOutput());
    }

    public function testDeniesMissingPrincipal(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_UPDATE, true, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(UpdateAnnouncementInterface::class)->process(new UpdateAnnouncementInput($principal, $this->draft()->announcementIdentifier(), Language::KOREAN, Category::UPDATES, new Title("manual ko"), new Content("manual body"), $this->draft()->publishedDate()), new UpdateAnnouncementOutput());
    }
}
