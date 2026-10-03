<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementOutput;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class DeleteAnnouncementOutputTest extends TestCase
{
    public function testSerializesSuppliedEntity(): void
    {
        $output = new DeleteAnnouncementOutput();
        $entity = new Announcement(new AnnouncementIdentifier('019c9b4c-0000-7000-8000-000000000001'), new TranslationSetIdentifier('019c9b4c-0000-7000-8000-000000000001'), Language::JAPANESE, Category::NEWS, new Title('sample-value'), new Content('sample-value'), new PublishedDate(new DateTimeImmutable('2026-10-03T01:02:03+00:00')));
        $output->setAnnouncements([$entity]);
        $this->assertSame(['announcements' => [[
            'announcementIdentifier' => (string) new AnnouncementIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'translationSetIdentifier' => (string) new TranslationSetIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'language' => Language::JAPANESE->value,
            'category' => Category::NEWS->value,
            'title' => (string) new Title('sample-value'),
            'content' => (string) new Content('sample-value'),
            'publishedDate' => '2026-10-03',
        ]]], $output->toArray());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame(['announcements' => []], (new DeleteAnnouncementOutput())->toArray());
    }
}
