<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Mockery;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DeleteAnnouncementTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testDeletesOnlySelectedDraftAndPreservesPublished(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_DELETE);
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $draftRepository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $publishedRepository = $this->app()->make(AnnouncementRepositoryInterface::class);
        $draftRepository->save($draft);
        $publishedRepository->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $this->publishedDate()));
        $other = new DraftAnnouncement(new AnnouncementIdentifier('019c9b4c-0000-7000-8000-000000000002'), $draft->sourceLanguage(), $draft->category(), $draft->contents());
        $draftRepository->save($other);
        $output = new DeleteAnnouncementOutput();
        $this->app()->make(DeleteAnnouncementInterface::class)->process(new DeleteAnnouncementInput($principal, $this->draft()->announcementIdentifier()), $output);
        $this->assertNull($draftRepository->findById($draft->announcementIdentifier()));
        $this->assertNotNull($draftRepository->findById($other->announcementIdentifier()));
        $this->assertSame($draft->contents()->toArray(), $publishedRepository->findById($draft->announcementIdentifier())?->contents()->toArray());
        $this->assertCount(1, $output->announcements());
    }

    #[PHPUnitGroup('useDb')]
    public function testMissingDraft(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_DELETE);
        $output = new DeleteAnnouncementOutput();
        $this->app()->make(DeleteAnnouncementInterface::class)->process(new DeleteAnnouncementInput($principal, $this->draft()->announcementIdentifier()), $output);
        $this->assertSame([], $output->announcements());
    }

    public function testDeniesWithoutPermission(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_DELETE, false);
        $repository = Mockery::mock(DraftAnnouncementRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');
        $this->app()->instance(DraftAnnouncementRepositoryInterface::class, $repository);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(DeleteAnnouncementInterface::class)->process(new DeleteAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new DeleteAnnouncementOutput());
    }

    public function testDeniesMissingPrincipal(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_DELETE, true, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(DeleteAnnouncementInterface::class)->process(new DeleteAnnouncementInput($principal, $this->draft()->announcementIdentifier()), new DeleteAnnouncementOutput());
    }
}
