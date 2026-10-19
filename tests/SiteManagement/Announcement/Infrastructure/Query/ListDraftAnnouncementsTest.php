<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Query;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsOutput;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class ListDraftAnnouncementsTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testListsUntranslatedDraftsWithLaravelPaginationAndAllContents(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT);
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        for ($index = 1; $index <= 3; ++$index) {
            $draft = $this->draft();
            if ($index > 1) {
                $draft->setLocalizedContent($this->localized(Language::KOREAN));
            }
            $repository->save(new DraftAnnouncement(new AnnouncementIdentifier('019c9b4c-0000-7000-8000-00000000000' . $index), $draft->sourceLanguage(), $draft->category(), $draft->contents()));
        }
        $query = $this->app()->make(ListDraftAnnouncementsInterface::class);
        $output = new ListDraftAnnouncementsOutput();
        $query->process(new ListDraftAnnouncementsInput($principal, perPage: 2), $output);
        $first = $output->toArray();
        $this->assertSame(3, $first['total']);
        $this->assertSame(1, $first['current_page']);
        $this->assertSame(2, $first['last_page']);
        $this->assertCount(2, $first['announcements']);
        $this->app()->instance('request', Request::create('/draft-announcements?page=2'));
        $query->process(new ListDraftAnnouncementsInput($principal, perPage: 2), $output);
        $this->assertSame(2, $output->toArray()['current_page']);
        $this->assertSame('019c9b4c-0000-7000-8000-000000000003', $output->toArray()['announcements'][0]['announcementIdentifier']);
        $this->app()->instance('request', Request::create('/draft-announcements'));
        $query->process(new ListDraftAnnouncementsInput($principal, perPage: 1, searchWords: 'ko body'), $output);
        $this->assertSame(2, $output->toArray()['total']);
        $this->assertSame(2, $output->toArray()['last_page']);
        $this->assertArrayHasKey('ja', $output->toArray()['announcements'][0]['contents']);
        $this->assertArrayHasKey('ko', $output->toArray()['announcements'][0]['contents']);
        $query->process(new ListDraftAnnouncementsInput($principal, searchWords: '%'), $output);
        $this->assertSame(0, $output->toArray()['total']);
    }

    #[PHPUnitGroup('useDb')]
    public function testPublishedRecordsAndDeletedDraftsAreExcluded(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT);
        $draft = $this->draft();
        $this->app()->make(AnnouncementRepositoryInterface::class)->save(new Announcement($draft->announcementIdentifier(), $draft->sourceLanguage(), $draft->category(), $draft->contents(), $this->publishedDate()));
        $repository = $this->app()->make(DraftAnnouncementRepositoryInterface::class);
        $repository->save($draft);
        $repository->delete($draft);
        $output = new ListDraftAnnouncementsOutput();
        $this->app()->make(ListDraftAnnouncementsInterface::class)->process(new ListDraftAnnouncementsInput($principal), $output);
        $this->assertSame(['announcements' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'per_page' => 50], $output->toArray());
    }

    public function testDeniesBeforeReadingDrafts(): void
    {
        $principal = $this->authorize(Action::ANNOUNCEMENT_VIEW_DRAFT, false);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListDraftAnnouncementsInterface::class)->process(new ListDraftAnnouncementsInput($principal), new ListDraftAnnouncementsOutput());
    }
}
