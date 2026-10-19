<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Infrastructure\Query;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsOutput;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class ListAnnouncementsTest extends AnnouncementTestCase
{
    #[PHPUnitGroup('useDb')]
    public function testUsesLaravelRequestPageAndReportsStablePageBoundaries(): void
    {
        $this->seedAnnouncements();
        $query = $this->app()->make(ListAnnouncementsInterface::class);
        $input = new ListAnnouncementsInput(Language::JAPANESE, perPage: 2);
        $output = new ListAnnouncementsOutput();
        $this->app()->instance('request', Request::create('/announcements'));
        $query->process($input, $output);
        $first = $output->toArray();
        $this->assertSame(1, $first['current_page']);
        $this->assertSame(2, $first['last_page']);
        $this->assertSame(3, $first['total']);
        $this->assertSame(2, $first['per_page']);
        $this->assertSame(['019c9b4c-0000-7000-8000-000000000001', '019c9b4c-0000-7000-8000-000000000002'], array_column($first['announcements'], 'announcement_id'));
        $this->app()->instance('request', Request::create('/announcements?page=2'));
        $query->process($input, $output);
        $second = $output->toArray();
        $this->assertSame(2, $second['current_page']);
        $this->assertSame(2, $second['last_page']);
        $this->assertSame(3, $second['total']);
        $this->assertSame(['019c9b4c-0000-7000-8000-000000000003'], array_column($second['announcements'], 'announcement_id'));
        $this->app()->instance('request', Request::create('/announcements?page=3'));
        $query->process($input, $output);
        $this->assertSame([], $output->toArray()['announcements']);
        $this->assertSame(3, $output->toArray()['current_page']);
        $this->assertSame(3, $output->toArray()['total']);
    }

    #[PHPUnitGroup('useDb')]
    public function testLanguageAndSearchFilterThePaginationTotal(): void
    {
        $this->seedAnnouncements();
        $query = $this->app()->make(ListAnnouncementsInterface::class);
        $output = new ListAnnouncementsOutput();
        $this->app()->instance('request', Request::create('/announcements?page=2'));
        $query->process(new ListAnnouncementsInput(Language::KOREAN, perPage: 1, sort: 'title', searchWords: 'ko body'), $output);
        $result = $output->toArray();
        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['last_page']);
        $this->assertSame(1, $result['per_page']);
        $this->assertSame('ko title', $result['announcements'][0]['title']);
        $this->assertSame('019c9b4c-0000-7000-8000-000000000003', $result['announcements'][0]['announcement_id']);
        $this->app()->instance('request', Request::create('/announcements'));
        $query->process(new ListAnnouncementsInput(Language::KOREAN, searchWords: 'ja title'), $output);
        $this->assertSame(0, $output->toArray()['total']);
        $this->assertSame([], $output->toArray()['announcements']);
        $query->process(new ListAnnouncementsInput(Language::KOREAN, searchWords: '%'), $output);
        $this->assertSame(0, $output->toArray()['total']);
    }

    #[PHPUnitGroup('useDb')]
    public function testEmptyResultsHaveMetadataAndDoNotExposeDraftsOrUntranslatedContent(): void
    {
        $this->app()->make(DraftAnnouncementRepositoryInterface::class)->save($this->draft());
        $output = new ListAnnouncementsOutput();
        $this->app()->make(ListAnnouncementsInterface::class)->process(new ListAnnouncementsInput(Language::JAPANESE), $output);
        $this->assertSame(['announcements' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'per_page' => 50], $output->toArray());
        $this->seedAnnouncements();
        $this->app()->make(ListAnnouncementsInterface::class)->process(new ListAnnouncementsInput(Language::ENGLISH), $output);
        $this->assertSame(['announcements' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'per_page' => 50], $output->toArray());
    }

    private function seedAnnouncements(): void
    {
        $repository = $this->app()->make(AnnouncementRepositoryInterface::class);
        for ($index = 1; $index <= 3; ++$index) {
            $draft = $this->draft();
            if ($index > 1) {
                $draft->setLocalizedContent($this->localized(Language::KOREAN));
            }
            $repository->save(new Announcement(
                new AnnouncementIdentifier('019c9b4c-0000-7000-8000-00000000000' . $index),
                $draft->sourceLanguage(),
                $draft->category(),
                $draft->contents(),
                $this->publishedDate(),
            ));
        }
    }
}
