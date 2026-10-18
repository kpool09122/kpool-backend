<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class DraftAnnouncementReadModelTest extends AnnouncementTestCase
{
    public function testSerializesAvailableTranslationsWithoutRequiringAllLanguages(): void
    {
        $draft = $this->draft();
        $draft->setLocalizedContent($this->localized(Language::KOREAN));
        $readModel = new DraftAnnouncementReadModel((string) $draft->announcementIdentifier(), $draft->sourceLanguage()->value, $draft->category()->value, $draft->contents()->toArray(), $draft->publishedDate()->value());
        $this->assertSame([
            'announcementIdentifier' => (string) $draft->announcementIdentifier(),
            'sourceLanguage' => 'ja',
            'category' => 1,
            'contents' => ['ja' => ['title' => 'ja title', 'content' => 'ja body'], 'ko' => ['title' => 'ko title', 'content' => 'ko body']],
            'publishedDate' => '2026-10-01',
        ], $readModel->toArray());
    }
}
