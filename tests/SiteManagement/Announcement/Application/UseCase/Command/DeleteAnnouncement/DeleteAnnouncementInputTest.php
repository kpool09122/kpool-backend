<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
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
