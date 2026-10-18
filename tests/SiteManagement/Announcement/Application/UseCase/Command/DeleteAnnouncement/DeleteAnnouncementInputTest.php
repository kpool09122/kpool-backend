<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteAnnouncementInput(
            $principalIdentifier,
            $announcementIdentifier,
        );
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame((string) $announcementIdentifier, (string) $input->announcementIdentifier());
    }
}
