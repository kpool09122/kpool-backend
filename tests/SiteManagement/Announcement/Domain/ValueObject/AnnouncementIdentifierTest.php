<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class AnnouncementIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $announcementIdentifier = new AnnouncementIdentifier($ulid);
        $this->assertSame($ulid, (string)$announcementIdentifier);
    }

    /**
     * 異常系: ulidが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $ulid = 'invalid-ulid';
        $this->expectException(InvalidArgumentException::class);
        new AnnouncementIdentifier($ulid);
    }
}
