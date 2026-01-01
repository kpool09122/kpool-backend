<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Query\GetGroup\GetGroupInput;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $langauge = Language::KOREAN;
        $input = new GetGroupInput($groupIdentifier, $langauge);
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($langauge->value, $input->language()->value);
    }
}
