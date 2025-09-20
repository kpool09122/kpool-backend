<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;

class FoundedInTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $date = new DateTimeImmutable('1997-04-25');
        $foundedIn = new FoundedIn($date);
        $this->assertSame($date, $foundedIn->value());
    }

    /**
     * 異常系：未来日付が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsInvalidArgumentException(): void
    {
        $date = new DateTimeImmutable('2099-01-01');
        $this->expectException(InvalidArgumentException::class);
        new FoundedIn($date);
    }
}
