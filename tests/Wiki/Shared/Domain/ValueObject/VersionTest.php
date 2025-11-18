<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class VersionTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $version = 1;
        $this->assertSame($version, new Version($version)->value());
    }

    /**
     * 異常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $version = new Version(0);
    }
}
