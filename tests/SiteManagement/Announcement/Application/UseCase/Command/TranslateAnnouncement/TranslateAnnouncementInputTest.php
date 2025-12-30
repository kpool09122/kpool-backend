<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement\TranslateAnnouncementInput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateAnnouncementInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $announcementIdentifier = new AnnouncementIdentifier(StrTestHelper::generateUlid());
        $input = new TranslateAnnouncementInput(
            $userIdentifier,
            $announcementIdentifier,
        );
        $this->assertSame((string)$announcementIdentifier, (string)$input->announcementIdentifier());
    }
}
