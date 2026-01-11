<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\PlanDescription;
use Tests\Helper\StrTestHelper;

class PlanDescriptionTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $description = 'test-description';
        $planDescription = new PlanDescription($description);
        $this->assertSame($description, (string)$planDescription);
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlanDescription(StrTestHelper::generateStr(PlanDescription::MAX_LENGTH + 1));
    }
}
