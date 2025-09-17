<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\Shared\ValueObject\Translation;
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
        $translation = Translation::JAPANESE;
        $input = new GetAnnouncementInput($announcementIdentifier, $translation);
        $this->assertSame((string)$announcementIdentifier, (string)$input->announcementIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
