<?php

namespace Tests\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Group\UseCase\Query\GetGroup\GetGroupInput;
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
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $input = new GetGroupInput($groupIdentifier, $translation);
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
        $this->assertSame($translation->value, $input->translation()->value);
    }
}
