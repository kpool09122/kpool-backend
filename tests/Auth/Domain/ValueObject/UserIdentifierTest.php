<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class UserIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $userIdentifier = new UserIdentifier($ulid);
        $this->assertSame($ulid, (string)$userIdentifier);
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
        new UserIdentifier($ulid);
    }
}
