<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Tests\Helper\StrTestHelper;

class MemberIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $memberIdentifier = new MemberIdentifier($ulid);
        $this->assertSame($ulid, (string)$memberIdentifier);
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
        new MemberIdentifier($ulid);
    }
}
