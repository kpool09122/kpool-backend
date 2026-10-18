<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishAnnouncementInputTest extends TestCase
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
        $input = new PublishAnnouncementInput(
            $principalIdentifier,
            $announcementIdentifier,
        );
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame((string) $announcementIdentifier, (string) $input->announcementIdentifier());
    }
}
