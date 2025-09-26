<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\RejectUpdatedGroup;

use Source\Wiki\Group\Application\UseCase\Command\RejectUpdatedGroup\RejectUpdatedGroupInput;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectUpdatedGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $input = new RejectUpdatedGroupInput(
            $groupIdentifier,
        );
        $this->assertSame((string)$groupIdentifier, (string)$input->groupIdentifier());
    }
}
