<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;

class SongIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $songIdentifier = new SongIdentifier($id);
        $this->assertSame($id, (string)$songIdentifier);
    }

    /**
     * 異常系: idが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new SongIdentifier($id);
    }
}
