<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Tests\Helper\StrTestHelper;

class ResourceIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $resourceIdentifier = new ResourceIdentifier($id);
        $this->assertSame($id, (string)$resourceIdentifier);
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
        new ResourceIdentifier($id);
    }
}
