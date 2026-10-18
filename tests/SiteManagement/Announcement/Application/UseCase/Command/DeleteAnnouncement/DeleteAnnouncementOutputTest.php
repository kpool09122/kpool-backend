<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DeleteAnnouncementOutputTest extends AnnouncementTestCase
{
    public function testSerializesAllLanguagesOnOneAnnouncement(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $output = new DeleteAnnouncementOutput();
        $entity = $draft;
        $output->setAnnouncements([$entity]);
        $expected = [
            'announcementIdentifier' => (string) $draft->announcementIdentifier(),
            'sourceLanguage' => 'ja',
            'category' => Category::NEWS->value,
            'contents' => ['ja' => ['title' => 'ja title', 'content' => 'ja body'], 'ko' => ['title' => 'ko title', 'content' => 'ko body']],
            'publishedDate' => '2026-10-01',
        ];
        $this->assertSame(['announcements' => [$expected]], $output->toArray());
        $this->assertSame([$entity], $output->announcements());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame(['announcements' => []], (new DeleteAnnouncementOutput())->toArray());
    }
}
