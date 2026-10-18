<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Tests\SiteManagement\Announcement\AnnouncementTestCase;

class AnnouncementContentsTest extends AnnouncementTestCase
{
    public function testUpdatingContentPreservesOtherLanguagesAndOriginalCollection(): void
    {
        $original = new AnnouncementContents($this->localized(Language::JAPANESE));
        $updated = $original->with($this->localized(Language::KOREAN));
        $this->assertNull($original->get(Language::KOREAN));
        $this->assertNotNull($updated->get(Language::JAPANESE));
        $this->assertNotNull($updated->get(Language::KOREAN));
    }

    public function testRejectsDuplicateLanguages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnnouncementContents($this->localized(Language::JAPANESE), $this->localized(Language::JAPANESE));
    }
}
