<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement\UpdateAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class UpdateAnnouncementOutputTest extends AnnouncementTestCase
{
    public function testSerializesAllLanguagesOnOneAnnouncement(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $output = new UpdateAnnouncementOutput();
        $entity = $draft;
        $output->setDraftAnnouncement($entity);
        $expected = [
            'announcementIdentifier' => (string) $draft->announcementIdentifier(),
            'sourceLanguage' => 'ja',
            'category' => Category::NEWS->value,
            'contents' => ['ja' => ['title' => 'ja title', 'content' => 'ja body'], 'ko' => ['title' => 'ko title', 'content' => 'ko body']],
            'publishedDate' => '2026-10-01',
        ];
        $this->assertSame($expected, $output->toArray());
        $this->assertSame($entity, $output->draftAnnouncement());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame([], (new UpdateAnnouncementOutput())->toArray());
    }
}
