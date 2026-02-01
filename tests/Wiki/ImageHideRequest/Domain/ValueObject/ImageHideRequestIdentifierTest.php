<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Tests\Helper\StrTestHelper;

class ImageHideRequestIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $imageHideRequestIdentifier = new ImageHideRequestIdentifier($id);
        $this->assertSame($id, (string)$imageHideRequestIdentifier);
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
        new ImageHideRequestIdentifier($id);
    }
}
