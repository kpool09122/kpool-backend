<?php

namespace Tests\Group\UseCase\Query\GetGroup;

use Businesses\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Group\UseCase\Query\GetGroup\GetGroupInput;
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
        $input = new GetGroupInput($groupIdentifier);
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
    }
}
