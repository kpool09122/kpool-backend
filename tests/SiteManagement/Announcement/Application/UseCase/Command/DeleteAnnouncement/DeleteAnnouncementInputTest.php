<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement\DeleteAnnouncementInput;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
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
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $input = new DeleteAnnouncementInput(
            $userIdentifier,
            $translationSetIdentifier,
        );
        $this->assertSame($userIdentifier, $input->userIdentifier());
        $this->assertSame((string) $translationSetIdentifier, (string) $input->translationSetIdentifier());
    }
}
