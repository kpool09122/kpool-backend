<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
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
        $id = StrTestHelper::generateUuid();
        $announcementIdentifier = new AnnouncementIdentifier($id);
        $this->assertSame($id, (string)$announcementIdentifier);
    }

    /**
     * 異常系: 値が不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new AnnouncementIdentifier($id);
    }
}
