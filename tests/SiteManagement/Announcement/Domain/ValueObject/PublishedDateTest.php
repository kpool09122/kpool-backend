<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;

class PublishedDateTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $date = new DateTimeImmutable('1997-04-25');
        $publishedDate = new PublishedDate($date);
        $this->assertSame($date, $publishedDate->value());
    }
}
