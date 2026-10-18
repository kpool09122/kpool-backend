<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements\ListDraftAnnouncementsOutput;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class ListDraftAnnouncementsOutputTest extends AnnouncementTestCase
{
    public function testSerializesDraftContentsAndPagination(): void
    {
        $draft = $this->draft();
        $readModel = new DraftAnnouncementReadModel((string) $draft->announcementIdentifier(), $draft->sourceLanguage()->value, $draft->category()->value, $draft->contents()->toArray(), $draft->publishedDate()->value());
        $output = new ListDraftAnnouncementsOutput();
        $output->output([$readModel], 2, 3, 5, 2);
        $this->assertSame(['announcements' => [$readModel->toArray()], 'current_page' => 2, 'last_page' => 3, 'total' => 5, 'per_page' => 2], $output->toArray());
    }
}
