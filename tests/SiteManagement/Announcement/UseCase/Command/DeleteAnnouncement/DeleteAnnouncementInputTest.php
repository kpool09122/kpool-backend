<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteAnnouncementInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUlid());
        $input = new DeleteAnnouncementInput(
            $announcementIdentifier,
        );
        $this->assertSame((string)$announcementIdentifier, (string)$input->announcementIdentifier());
    }
}
