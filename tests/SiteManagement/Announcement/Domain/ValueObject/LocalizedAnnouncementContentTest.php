<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class LocalizedAnnouncementContentTest extends TestCase
{
    public function testSerializesTitleAndBodyWithoutLosingUnicodeOrMarkup(): void
    {
        $content = new LocalizedAnnouncementContent(Language::KOREAN, new Title('안내 🎉'), new Content("# 본문\n한국어 내용"));
        $this->assertSame(Language::KOREAN, $content->language);
        $this->assertSame(['title' => '안내 🎉', 'content' => "# 본문\n한국어 내용"], $content->toArray());
    }
}
