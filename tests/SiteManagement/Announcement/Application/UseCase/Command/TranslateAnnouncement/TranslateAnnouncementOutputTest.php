<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class TranslateAnnouncementOutputTest extends AnnouncementTestCase
{
    public function testSerializesAllLanguagesOnOneAnnouncement(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $output = new TranslateAnnouncementOutput();
        $entity = $draft;
        $output->setAnnouncements([$entity]);
        $expected = [
            'announcementIdentifier' => (string) $draft->announcementIdentifier(),
            'sourceLanguage' => 'ja',
            'category' => Category::NEWS->value,
            'contents' => ['ja' => ['title' => 'ja title', 'content' => 'ja body'], 'ko' => ['title' => 'ko title', 'content' => 'ko body']],
        ];
        $this->assertSame(['announcements' => [$expected]], $output->toArray());
        $this->assertSame([$entity], $output->announcements());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame(['announcements' => []], (new TranslateAnnouncementOutput())->toArray());
    }
}
