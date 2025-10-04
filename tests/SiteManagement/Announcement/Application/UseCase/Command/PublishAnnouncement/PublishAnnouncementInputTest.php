<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement\PublishAnnouncementInput;
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $input = new PublishAnnouncementInput(
            $translationSetIdentifier,
        );
        $this->assertSame((string)$translationSetIdentifier, (string)$input->translationSetIdentifier());
    }
}
