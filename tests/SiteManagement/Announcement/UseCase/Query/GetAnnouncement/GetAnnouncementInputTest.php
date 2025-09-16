<?php

namespace Tests\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement\GetAnnouncementInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAnnouncementInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUlid());
        $input = new GetAnnouncementInput($announcementIdentifier);
        $this->assertSame((string)$announcementIdentifier, (string)$input->announcementIdentifier());
    }
}
