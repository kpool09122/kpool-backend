<?php

declare(strict_types=1);

namespace SiteManagement\User\Domain\ValueObject;

use InvalidArgumentException;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

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
        $accountIdentifier = new UserIdentifier($ulid);
        $this->assertSame($ulid, (string)$accountIdentifier);
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
